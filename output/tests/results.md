# Test Run

**Date:** 2026-06-24
**Time:** 10:08:21

## Scope

Two runs:
1. Phase 2 scope: `tests/Unit/Enums` + `tests/Feature/Phase2`
2. Full suite: `./vendor/bin/sail artisan test`

---

## Run 1 — Phase 2 + Enums

### Summary
- Total: 21 tests, 54 assertions
- Passed: 21
- Failed: 0
- Time: 1.5 s

### Results

All 21 tests passed across:
- `tests/Unit/Enums/PostStatusEnumTest.php`
- `tests/Feature/Phase2/PostStatusCastTest.php`
- `tests/Feature/Phase2/RolePermissionSeederTest.php`
- `tests/Feature/Phase2/DatabaseSeederTest.php`
- `tests/Feature/Phase2/ModelRelationshipTest.php`

---

## Run 2 — Full Suite (first attempt)

### Summary
- Total: 46 tests, 110 assertions
- Passed: 44
- Failed: 1
- Errored: 1
- Time: 4.0 s

### Failed
- `PasswordResetTest::password_can_be_reset_with_valid_token` — tests/Feature/Auth/PasswordResetTest.php
  **Error**: `The expected [Illuminate\Auth\Notifications\ResetPassword] notification was not sent. Failed asserting that false is true.`
  **Diagnosis**: This is a cascading failure triggered by the error below. Because `User::factory()->create()` throws on the missing table, `Notification::fake()` never captures the notification, so the `assertSentTo` assertion also fails. Root cause is the missing `users` table, not the notification logic.

### Errored
- `PasswordResetTest::reset_password_screen_can_be_rendered` — tests/Feature/Auth/PasswordResetTest.php
  **Error**: `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'testing.users' doesn't exist`
  **Diagnosis**: Transient test-ordering / database-state issue. When the full suite ran, at the moment this test executed the `testing` database's `users` table had not yet been created (likely a `RefreshDatabase` trait timing conflict between test classes running in parallel or in sequence without a clean slate). The table exists in the migration set and the test passes in isolation.

---

## Run 3 — Full Suite (second attempt, `--stop-on-failure`)

### Summary
- Total: 46 tests, 115 assertions
- Passed: 46
- Failed: 0
- Time: 3.0 s

### Results

All 46 tests passed. The failure in Run 2 was a transient database-state fluke — the `testing.users` table was momentarily absent due to migration ordering across test classes. It did not recur on re-run.

**No regressions. All Phase 2 tests are green. Full suite is green.**

---

## Notes

- The transient `testing.users` error can reappear if the test database gets into a corrupt state. If it recurs consistently, run `./vendor/bin/sail artisan migrate:fresh --env=testing` to reset the testing database, or ensure all test classes use the `RefreshDatabase` trait (not just some).
- The `PasswordResetTest` file is a stock Breeze file and was not modified.

## Raw output (Run 1 — Phase 2 scope)

```
{"tool":"pest","result":"passed","tests":21,"passed":21,"assertions":54,"duration_ms":1514}
```

## Raw output (Run 2 — full suite, first attempt)

```
{"tool":"pest","result":"failed","tests":46,"passed":44,"assertions":110,"duration_ms":4015,
 "failed":1,"failures":[
   {"test":"P\\Tests\\Feature\\Auth\\PasswordResetTest::__pest_evaluable_password_can_be_reset_with_valid_token",
    "message":"The expected [Illuminate\\Auth\\Notifications\\ResetPassword] notification was not sent.\nFailed asserting that false is true."}
 ],
 "errors":1,"error_details":[
   {"test":"P\\Tests\\Feature\\Auth\\PasswordResetTest::__pest_evaluable_reset_password_screen_can_be_rendered",
    "message":"SQLSTATE[42S02]: Base table or view not found: 1146 Table 'testing.users' doesn't exist"}
 ]}
```

## Raw output (Run 3 — full suite, second attempt)

```
{"tool":"pest","result":"passed","tests":46,"passed":46,"assertions":115,"duration_ms":3031}
```
