# TESTING CHECKLIST

## Authentication
- [ ] Owner login valid.
- [ ] Employee login valid.
- [ ] Invalid password rejected.
- [ ] Inactive user rejected.
- [ ] Employee cannot access admin routes.
- [ ] CSRF enabled.
- [ ] Session regenerated.

## Employee
- [ ] Create.
- [ ] Edit.
- [ ] Employee code unique.
- [ ] Activate/deactivate.
- [ ] Empty state works.

## Shift
- [ ] Create/edit/deactivate.
- [ ] Time validation.
- [ ] Cross-midnight handled.

## Schedule
- [ ] Assign work.
- [ ] Assign off.
- [ ] Unique employee/date.
- [ ] Copy week does not create duplicates.
- [ ] Employee sees only own schedule.

## Geofence
- [ ] Valid point inside radius accepted.
- [ ] Outside radius rejected.
- [ ] Bad latitude rejected.
- [ ] Bad longitude rejected.
- [ ] Poor accuracy rejected.
- [ ] Server recalculates distance.

## Camera
- [ ] Front camera opens mobile.
- [ ] Permission denied handled.
- [ ] Retake works.
- [ ] File validation works.
- [ ] Oversized upload rejected/processed according to rule.

## Check-in
- [ ] Requires active employee.
- [ ] Requires work schedule.
- [ ] Requires GPS.
- [ ] Requires selfie.
- [ ] Uses server timestamp.
- [ ] Late minutes correct.
- [ ] Double submit does not duplicate record.

## Check-out
- [ ] Requires check-in.
- [ ] Duplicate check-out blocked.
- [ ] Worked minutes correct.
- [ ] Early leave correct.
- [ ] Cross-midnight correct.

## Leave
- [ ] Submit.
- [ ] Approve.
- [ ] Reject.
- [ ] Employee cannot approve own request.
- [ ] Date validation.
- [ ] Audit created.

## Reports
- [ ] Date filters.
- [ ] Employee filters.
- [ ] Status filters.
- [ ] Totals match records.
- [ ] No hardcoded metrics.
- [ ] Export matches filter.

## UI
- [ ] 360px.
- [ ] 390px.
- [ ] 430px.
- [ ] 768px.
- [ ] 1024px.
- [ ] 1366px.
- [ ] No accidental horizontal scroll.
- [ ] Mobile bottom nav does not cover content.
- [ ] Desktop dropdown alignment.
- [ ] Loading, empty, success, error states.

## PWA
- [ ] Valid manifest.
- [ ] Icons present.
- [ ] Standalone mode.
- [ ] Service worker registration.
- [ ] No sensitive API caching.
- [ ] Auth logout does not reveal cached private screen.

## Production
- [ ] APP_DEBUG=false.
- [ ] HTTPS.
- [ ] Cron.
- [ ] Storage writable.
- [ ] `.env` inaccessible.
- [ ] private selfie inaccessible without authorization.
- [ ] database backup created.
