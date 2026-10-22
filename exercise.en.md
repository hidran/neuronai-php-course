# Exercise: extract an order without silently repairing it

Use the completed `Order`, `OrderLine`, `Address`, `OrderExtractor` and `OrderReview` classes. Work in a learner project with PHP 8.5 or later and installed Composer dependencies. Configure your own Anthropic credentials locally for the optional live run. Keep credentials out of screenshots and shared files.

1. Write three new synthetic emails. A complete order has two lines and a leading-zero ZIP. An incomplete order references previous customer details but states no name, address, prices or total. An incorrect order explicitly states a price that differs from the catalog and a total matching that incorrect price.
2. Independently write the expected source facts for each email. Keep this oracle outside the prompt. Represent unstated optional facts as `null`. Store stated amounts as integer EUR cents.
3. Check malformed input shape separately from the runtime rules. Use `Deserializer::fromJson()` for a non-array `lines` value. Use `Validator::validate()` for an invalid nested SKU, a zero quantity and an empty order. Keep `ArrayOf(OrderLine::class)`, `Regex` and `GreaterThan(0)` on the typed DTOs.
4. Show that all three faithful candidates pass runtime validation. Then compare every extracted field against the source oracle, including line count, SKU, quantity, stated prices, stated total and address fields. Allow the two requested lines to arrive in either order.
5. Run deterministic business review against `BW-1120 => 1250` and `WF-0080 => 4500`. The incomplete order needs review. The incorrect order must preserve the stated price and total and report catalog differences.
6. Demonstrate that a positive but invented quantity can pass property rules while failing the source comparison. Do not save any order.
7. Optionally run the same three emails through fresh extractor instances with `maxRetries: 1`, and subscribe to `Validated` to report violation counts. Treat exhausted extraction as failure, never as a passed exercise.

## Worked solution

Copy `files/exercise-order-extraction.php` into the completed project's `examples/exercise-order-extraction.php`. The script includes the three synthetic emails and the independent expected facts. It reuses the lesson's DTOs and review class and does not need extra fixture files.

In PhpStorm Terminal, run:

```text
php examples/exercise-order-extraction.php
php examples/exercise-order-extraction.php --live
```

The first command runs deterministic checks without loading credentials or contacting a provider. The second repeats those checks, then makes three live extraction requests. Each request has at most two attempts: the initial attempt and one retry. A validation event counts attempts that reach validation, so it does not count a response that fails earlier during deserialization. The code catches structured-processing failures, prints an explicit rejection, and exits unsuccessfully if any case is rejected. Source or business mismatches also fail the process. Provider and configuration errors are not converted into success.

Expected deterministic output includes:

```text
Shape: malformed lines rejected.
Local valid: contract, source, review verified.
Local incomplete: contract, source, review verified.
Local incorrect: contract, source, review verified.
Runtime rules: malformed nested line and empty order rejected.
Source fidelity: valid but invented quantity detected.
Persistence: not performed.
Exercise complete.
```

The local run also prints that live extraction was skipped. On successful live extraction, each case prints `Live <case>: contract, source, review verified.` The incomplete case reports four missing facts. The incorrect case reports `Catalog price differs: BW-1120.` and `Catalog total differs: expected 5750.` Counts of validation events can vary. A particular provider response is not guaranteed.

The incorrect email says EUR 12.00 for one widget and EUR 57.00 for the whole order. A faithful extraction retains `1200` and `5700`. The catalog expects `1250` and `5750`. A model that silently replaces the stated amounts with catalog values would fail the source check even though the numbers look plausible. Missing amounts stay null rather than being calculated from catalog entries. The incomplete order is a valid extraction with unresolved business issues.

## Acceptance criteria

- The default run passes without credentials or provider calls, and the malformed nested line and empty order are rejected.
- All three known candidates pass the DTO runtime rules, but only the complete candidate has no business issues.
- The complete order contains exactly two typed lines and preserves ZIP `04001` as a string. Its unstated city remains null.
- The incomplete order preserves null name, address, unit price and total. A reference to a previous order provides none of those facts.
- The incorrect order preserves the stated amounts and yields the two exact catalog issues above.
- The deliberately invented positive quantity passes runtime rules and fails the source oracle.
- A successful live run verifies all three cases. A rejection, invented field or unexpected review causes a nonzero exit status. Record actual results rather than claiming the model always succeeds.
- No order is persisted. Passing shape, rules, source checks and these limited review checks does not establish payment, stock availability, a deliverable address or authorization to fulfill an order.

Explain why correcting an invalid source SKU during a retry could improve its format while making the extraction less faithful. Validation asks whether a value satisfies a rule. The source oracle asks whether the email actually stated it. Business review asks whether the extracted facts satisfy the application's catalog checks. Each answers a different question.
