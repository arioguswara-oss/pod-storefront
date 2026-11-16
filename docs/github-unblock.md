# GitHub CI and ZIP delivery — owner actions

## Actions billing blocker

Run: https://github.com/arioguswara-oss/pod-storefront/actions/runs/43923170659

GitHub reported: **The job was not started because your account is locked due
to a billing issue.** There were no runner steps or CI artifacts. This is
separate from the passing local tests.

1. Sign in to GitHub as the repository owner.
2. Open account Settings → Billing and licensing (or the billing menu shown by
   your GitHub interface), inspect the account lock and resolve the stated issue.
   Only the owner should review payment methods or authorize any charge.
3. Open the failed Actions run and choose **Re-run all jobs**. The workflow has
   no deployment actions. Check all PHP matrix jobs and browser/rollback checks.
4. After a passing run, download `wordpress-installable-packages` from Artifacts.
   Extract the artifact wrapper; the two independent WordPress ZIPs are inside.
   `browser-checks` contains generated test reports and screenshots.

Do not label GitHub CI as passed until the rerun succeeds. The documentation-only
blocker commit uses `[skip ci]` to avoid retrying a known locked account.

## Release asset authentication

Creating branches/commits/PRs succeeds. A separate upload request to
`uploads.github.com` returned HTTP 401 Bad credentials; no release ZIP was
uploaded. The failed empty draft was removed rather than leaving a misleading
download page.

Reconnect GitHub in ChatGPT/Codex **Settings → Plugins → GitHub** if needed and
verify repository access. If upload authentication still fails, use your own
authenticated GitHub CLI or GitHub release editor to attach the built ZIPs to a
draft release. Do not paste tokens/passwords into chat or commit them. Publishing
a release and activating a theme are separate actions; neither is automatic.

## Rebuild without waiting for CI

On a computer with Git and Python 3.9+:

```sh
git clone --branch codex/pod-store-theme-v0.1.0 --single-branch https://github.com/arioguswara-oss/pod-storefront.git
cd pod-storefront
python3 scripts/build.py
```

The `dist/` directory contains `pod-store-theme-0.1.0.zip`,
`pod-simple-checkout-0.1.0-dev.zip`, `manifest.json` and `SHA256SUMS`.
The plugin is a scaffold awaiting theme review. These commands do not access
GraphicTeeStore.com or require WordPress/database setup.

If owner-approved delivery through Google Drive is used, it is a separate
transfer of these review artifacts. No sharing permissions should be widened.
