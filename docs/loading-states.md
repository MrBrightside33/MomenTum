# Loading states

The application uses shared loading helpers so network activity has consistent visual, accessible, and error states.

## Skeletons and asynchronous regions

Reusable skeleton components live in `resources/views/components/sk`. Template snapshots used by JavaScript are registered in `resources/views/skeletons/templates.blade.php`.

Use `withSkeleton` from `resources/js/loading.js` for an existing region that is replaced by an asynchronous result. It delays the skeleton briefly to avoid flashing on fast requests, preserves the region height, exposes `aria-busy`, announces loading through the live region, and provides a retry state when a request fails.

For an initially lazy-loaded region:

1. Render a container with a stable `id`, `data-lazy-region` URL, and `data-lazy-skeleton` template name.
2. Render its skeleton inside the container for the initial page response.
3. Return a partial from the endpoint whose root element has the same `id`.
4. Add the matching skeleton template and initialize the region from `resources/js/lazy-regions.js`.

Admin overview and report data are examples. Their shell pages render first, then the authorized data endpoints return the matching HTML partials.

## Requests and controls

Use `request` from `resources/js/http.js` instead of calling `fetch` directly. It applies same-origin credentials, CSRF headers for writes, the request timeout, JSON/text/blob parsing, and shared HTTP errors. Pass a stable `key` when a newer request should cancel an older request with the same purpose.

`resources/js/loading.js` also provides:

- `setButtonLoading` for button pending, success, and error states.
- `setRowState` for inline row updates.
- `setInlineLoading` for small values updated independently.

Call page-specific initializers after the `page:load` event when a feature must work after soft navigation. Initialization should not accumulate duplicate listeners.

## Navigation and task deletion

`resources/js/soft-nav.js` handles eligible same-origin navigation between app pages with the same layout. It keeps browser history and falls back to a normal page navigation if the response cannot be safely swapped. `resources/js/nav-progress.js` displays its progress indicator.

Task deletion moves the task to the trash and offers an eight-second Undo action. Soft-deleted tasks are permanently pruned after `TASK_RETENTION_DAYS` days (30 by default); `APP_SIMULATE_LATENCY` can enable local-only test latency. The development latency control accepts the preset values 800, 2000, and 4000 ms.

## Accessibility and motion

Skeleton decoration is hidden from assistive technology; loading containers use `aria-busy`, and status changes use the shared live region. Skeleton animation respects the reduced-motion preference in the application stylesheet.
