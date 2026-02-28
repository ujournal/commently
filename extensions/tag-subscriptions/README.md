# Tag Subscriptions

Stores which tags (topics) each user is subscribed to and exposes an API so the custom-frontend can show a filtered feed and persist preferences.

## API contract (for integration with custom-frontend or other extensions)

- **GET /api/tag-subscriptions**  
  Returns `{ "slugs": ["slug1", "slug2", ...] }` — tag slugs the current user is subscribed to. Guests get `{ "slugs": [] }`.

- **PUT /api/tag-subscriptions**  
  Body: `{ "slugs": ["slug1", "slug2", ...] }`  
  Replaces the current user’s subscribed tags with the given list. Requires a logged-in user (401 otherwise).

Any extension that implements these two endpoints with the same request/response format can be used by custom-frontend for “Save feed” and feed filtering.
