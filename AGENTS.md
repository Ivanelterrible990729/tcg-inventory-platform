# AGENTS.md

## Project Overview

TCG Inventory Platform is a Laravel + React web application designed for inventory management and e-commerce operations for local Trading Card Game (TCG) stores.

The application manages products such as:

- Single cards
- Sealed TCG products
- Deck boxes 
- Sleeves
- Playmats
- Other TCG accessories

The first supported TCG and external card integration will focus on **Yu-Gi-Oh!**.

The project is currently under active development. Features must be implemented incrementally according to the current GitHub Issues and milestones.

---

## Technology Stack

### Backend

- PHP
- Laravel
- MySQL
- REST API

### Frontend

- React
- TypeScript / JavaScript  
- Tailwind CSS

### Development

- Git
- GitHub
- Composer
- Node.js
- npm

Do not introduce major frameworks, architectural patterns, or dependencies unless they provide a clear benefit to the current requirement.

---

## Documentation

Project documentation is available under `docs/`.

Important database documentation:

- `docs/database/schema.dbml` — editable database schema and relationships.
- `docs/database/schema.png` — visual database diagram.
- `docs/database/data-dictionary.md` — description of tables, fields, constraints, relationships, and business rules.

Before making changes involving the database, models, relationships, or migrations, review the relevant database documentation.

When a database change is intentionally introduced, identify whether the corresponding documentation also needs to be updated.

Do not modify the documented database design simply to make an implementation easier unless the requested task explicitly requires a schema change.

---

# Core Domain Rules

## Products

`products` is the central inventory entity.

A Product represents one independently managed inventory item.

If two items require independent stock management, they must be represented as separate Products.

Examples:

- Two different card rarities are separate Products.
- Two different card conditions are separate Products.
- Two different card printings are separate Products.
- Deck boxes of different colors may be separate Products.

The V1 does **not** use a product variants system.

Do not introduce product options or product variants unless the project requirements explicitly change.

---

## Single Cards

A Product represents a single card when it has an associated `card_details` record.

General commercial and inventory information belongs to `products`.

Card-specific information belongs to `card_details`.

Examples of card-specific information include:

- Card name
- Set
- Set code
- Card number
- Rarity
- Condition
- Language
- Edition
- Foil type
- External card provider information

Do not move general Product responsibilities into `card_details`.

---

## External TCG Data

External APIs are used as catalog/data providers.

They are **not** the source of truth for store-specific information.

External APIs may provide:

- Card metadata
- Sets
- Printings
- Rarities
- Images
- External identifiers

The application remains responsible for:

- Stock
- Reserved stock
- Cost
- Sale price
- Condition
- Availability
- Store-specific product information

The first planned external provider is YGOPRODeck for Yu-Gi-Oh!.

External integrations should be isolated behind application services/providers instead of coupling controllers or domain models directly to a specific external API.

---

## Users and Customers

Authentication and authorization information belongs to `users`.

Customer-related information belongs to `customers`.

Every User must have an associated Customer record regardless of role.

This applies to:

- Admin
- Staff
- Customer

This rule exists because any platform user may eventually perform a purchase.

Operations that create both a User and Customer must maintain data consistency. Prefer explicit, testable business logic and use database transactions when multiple related writes must succeed or fail together.

---

## Inventory

Inventory changes should be traceable.

`products.stock` represents the current stock value.

`products.reserved_stock` represents stock temporarily committed to purchase/order processes.

Relevant stock changes should generate an `inventory_movements` record.

Avoid implementing business workflows that silently modify stock without preserving the corresponding inventory history.

Inventory logic should be centralized instead of duplicated across controllers or unrelated components.

---

## Orders

Orders must preserve historical purchase information.

`order_items` contains snapshots of relevant product information such as:

- Product name
- SKU
- Unit price

Changes to a Product after an order has been placed must not rewrite the historical commercial information stored in previous orders.

---

## Fulfillment

V1 supports **in-store pickup only**.

The project currently does not support:

- Shipping
- Customer shipping addresses
- Shipping carriers
- Tracking numbers
- Multiple fulfillment methods

Do not introduce shipping infrastructure unless explicitly required by a future feature or milestone.

Pickup operations are represented through `pickups`.

---

# Backend Guidelines

Keep controllers focused on HTTP responsibilities.

Controllers should generally:

1. Receive validated input.
2. Check authorization when necessary.
3. Delegate business operations.
4. Return the appropriate response.

Avoid placing complex business logic directly inside controllers.

Use Laravel Form Requests when request validation becomes substantial or reusable.

Use dedicated Actions/Services when an operation contains meaningful business logic or coordinates multiple entities.

Do not introduce an Action or Service merely to wrap a trivial one-line model operation.

Use database transactions for operations where multiple related database changes must succeed or fail together.

Use Eloquent relationships where appropriate.

Avoid unnecessary raw SQL when Eloquent or the query builder provides a clear solution.

---

# API Guidelines

Keep API responses predictable and consistent.

Use appropriate HTTP status codes.

Validation failures must return useful validation information.

Do not expose sensitive model fields.

When creating endpoints, follow existing route, controller, resource, and response conventions in the repository before introducing new patterns.

---

# Frontend Guidelines

The frontend should prioritize clarity and usability, especially for administrative inventory workflows.

Use React components with clear responsibilities.

Prefer reusable components when the same UI pattern appears in multiple places.

Avoid creating abstractions prematurely for components that currently have only one use case.

Use Tailwind CSS for styling.

Follow existing UI patterns before introducing new ones.

Administrative interfaces should prioritize:

- Clear information hierarchy
- Readable tables
- Simple forms
- Visible validation feedback
- Clear status indicators
- Predictable actions
- Efficient inventory workflows

Visual complexity should not make common inventory operations harder to perform.

---

# Testing Guidelines

Business-critical behavior should be covered by automated tests.

Prioritize tests for:

- Authorization
- Validation
- Relationships
- Business rules
- Inventory operations
- Transactions
- Order creation
- Stock reservation
- External API normalization

When implementing a feature:

1. Identify its important behavior.
2. Add or update appropriate tests.
3. Run the relevant test suite.
4. Report failing tests instead of hiding or bypassing them.

Do not change existing tests solely to make an incorrect implementation pass.

---

# Development Workflow

Before modifying code:

1. Read the current task carefully.
2. Inspect the relevant existing implementation.
3. Review related documentation when necessary.
4. Identify existing conventions in the repository.
5. Determine the smallest reasonable change that satisfies the requirement.

During implementation:

- Keep changes scoped to the requested task.
- Do not implement unrelated future requirements.
- Do not perform broad refactors unless necessary.
- Reuse existing conventions and components.
- Keep business rules explicit and testable.
- Avoid speculative abstractions.

After implementation:

1. Run relevant tests.
2. Run applicable formatting/linting tools.
3. Review the resulting diff.
4. Verify that unrelated files were not modified.
5. Identify any documentation that may now be outdated.

---

# Working With GitHub Issues

GitHub Issues describe functional requirements and acceptance criteria.

Treat the current Issue as the functional source of truth for the requested feature.

Do not assume that the entire Issue must be implemented in a single change.

When instructed to implement only part of an Issue:

- Implement only that part.
- Preserve compatibility with the remaining requirements.
- Do not prematurely implement later steps.

If requirements conflict with the current codebase or project documentation, identify the conflict before making a major architectural decision.

---

# Scope Control

This project is developed incrementally.

Features planned for later milestones should not be implemented unless they are necessary for the current task.

In particular, do not prematurely introduce:

- Product variants
- Shipping
- Customer addresses
- Multiple stores or warehouses
- Supplier management
- Advanced returns
- Coupons
- Reviews
- Price history

Prefer completing the current V1 requirements over preparing speculative infrastructure for possible future features.

---

# Instructions for Coding Agents

When receiving an implementation task:

1. Inspect the relevant files before writing code.
2. Follow this `AGENTS.md`.
3. Follow the current database documentation.
4. Follow existing repository conventions.
5. Ask or report ambiguity when it materially affects architecture or business behavior.
6. Keep the implementation focused on the requested scope.
7. Add or update relevant tests.
8. Run relevant tests and validation tools.
9. Summarize what changed.
10. Mention important architectural decisions, assumptions, or unresolved concerns.

When asked to **analyze or plan only**, do not modify files.

When asked to implement a specific portion of a feature, do not automatically implement the entire related GitHub Issue.

Correctness, maintainability, and clear domain behavior are more important than maximizing the amount of generated code.
