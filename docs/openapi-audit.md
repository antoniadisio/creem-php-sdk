# Creem OpenAPI Spec Audit

This audit captures the current shape of the committed OpenAPI contract at `tests/Fixtures/OpenApi/creem-openapi.json` and the known issues that matter while the handwritten PHP SDK is being maintained. This document focuses on the API surface and the risks that affect the SDK design, fixture coverage, and typed test expectations.

## Current Contract Status

- The spec defines both production and test server URLs.
- API authentication is modeled through the `x-api-key` header.
- The `servers` entries already distinguish the production and test environments through vendor metadata, and those labels should be preserved in the committed contract.

## Structure That Needs Ongoing Attention

- `allOf` is present in nested schema properties, not top-level schemas.
  Examples: `ProductListEntity.pagination`, `CustomerListEntity.pagination`, `TransactionListEntity.pagination`, `StatsSummaryEntity.totals`.
- `oneOf` is present in nested relationship fields.
  Examples: `SubscriptionEntity.product`, `SubscriptionEntity.customer`, `CheckoutEntity.product`, `CheckoutEntity.customer`, `CheckoutEntity.subscription`.
- Nullable fields are common.
  The spec currently contains 40 `nullable: true` properties, so the SDK needs explicit handling for both nullable and missing values.

## API Shape Risks

- Several read operations use collection-style paths with query IDs instead of canonical `/{id}` resource paths.
  Examples: `GET /v1/products?product_id=...`, `GET /v1/subscriptions?subscription_id=...`, `GET /v1/transactions?transaction_id=...`.
- Some endpoint paths encode actions directly in the URL.
  Examples: `GET /v1/customers/list`, `DELETE /v1/discounts/{id}/delete`.
- Pagination parameters are modeled as `number`, not integer-like types.
  `page_number` and `page_size` are currently `type: number` on `GET /v1/products/search`, `GET /v1/customers/list`, and `GET /v1/transactions/search`.
- Query filters also carry ID semantics on search endpoints.
  `GET /v1/transactions/search` accepts `customer_id`, `order_id`, and `product_id` as query parameters.

## Naming Risks For PHP

- Some schema names are generic enough that they may be awkward as long-lived consumer DTO names.
  Examples: `Text`, `Checkbox`, `CustomField`, `FeatureEntity`.
- Some related schema names are close enough to be easy to confuse in a handwritten PHP API surface.
  Examples: `FeatureFileEntity`, `FileFeatureEntity`, `ProductFeatureEntity`.
- Current operation IDs are usable, but a few are semantically misleading because they imply canonical resource retrieval on non-resource paths.
  Examples: `retrieveProduct`, `retrieveCustomer`, `retrieveSubscription`, `retrieveCheckout`, `retrieveDiscount`.

## Path Parameter Consistency

- Current `{id}` path templates match the declared path parameter names.
- No path-parameter naming mismatches were found in the current spec.
