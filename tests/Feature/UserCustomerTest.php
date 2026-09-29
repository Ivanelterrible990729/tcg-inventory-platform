<?php

use App\Enums\RoleEnum;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('users of every role have a customer that belongs to them', function (RoleEnum $role) {
    $user = User::factory()->create(['role' => $role->value]);
    $customer = $user->customer;

    expect($user->customer()->count())->toBe(1);

    $customer->update(['phone' => '5551234567']);

    expect($user->fresh()->customer)->toBeInstanceOf(Customer::class)
        ->and($user->fresh()->customer->is($customer))->toBeTrue()
        ->and($customer->user->is($user))->toBeTrue()
        ->and($customer->phone)->toBe('5551234567');
})->with(RoleEnum::cases());

test('users default to active customers and active status is a boolean', function () {
    $user = User::factory()->create()->refresh();

    expect($user->role)->toBe(RoleEnum::CUSTOMER->value)
        ->and($user->is_active)->toBeTrue();

    $user->update(['is_active' => false]);

    expect($user->fresh()->is_active)->toBeFalse();
});

test('customer phone can be null', function () {
    $customer = User::factory()->create()->customer;

    expect($customer->fresh()->phone)->toBeNull();
});

test('a user cannot have multiple customers', function () {
    $user = User::factory()->create();

    expect(fn () => $user->customer()->create())->toThrow(QueryException::class);
});

test('a customer must reference an existing user', function () {
    expect(fn () => DB::table('customers')->insert(['user_id' => 999999]))
        ->toThrow(QueryException::class);
});

test('a customer cannot have a null user', function () {
    expect(fn () => DB::table('customers')->insert(['user_id' => null]))
        ->toThrow(QueryException::class);
});

test('deleting a user deletes only their customer', function () {
    $user = User::factory()->create();
    $customer = $user->customer;
    $otherCustomer = User::factory()->create()->customer;

    $user->delete();

    expect($customer->fresh())->toBeNull()
        ->and($otherCustomer->fresh())->not->toBeNull();
});

test('updating a user preserves their existing customer', function () {
    $user = User::factory()->create();
    $customer = $user->customer;
    $customer->update(['phone' => '5551234567']);

    $user->update(['name' => 'Updated User', 'role' => RoleEnum::STAFF->value]);

    expect($user->fresh()->customer->is($customer))->toBeTrue()
        ->and($user->fresh()->customer->phone)->toBe('5551234567')
        ->and($user->customer()->count())->toBe(1);
});

test('customer creation rolls back with the user transaction', function () {
    $userId = null;

    try {
        DB::transaction(function () use (&$userId) {
            $user = User::factory()->create();
            $userId = $user->id;

            expect($user->customer)->toBeInstanceOf(Customer::class);

            throw new RuntimeException('Cancel creation');
        });
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Cancel creation');
    }

    $this->assertDatabaseMissing('users', ['id' => $userId]);
    $this->assertDatabaseMissing('customers', ['user_id' => $userId]);
});
