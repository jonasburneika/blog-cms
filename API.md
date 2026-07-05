# Blog Content API Specification

A platform-agnostic API contract for receiving blog content from a central content generation system. Any CMS (PrestaShop, WordPress, OpenCart, Magento, Laravel, custom) implements this single endpoint to accept content.

---

## Endpoint

```
POST {site_url}/api/gateway
POST {site_url}/api/gateway?locale=en
```

Single URL. Only `POST` required (optionally support `GET` for read actions). All operations are controlled by `action` and `resource` parameters in the request body.

The optional `locale` query parameter sets the content language. If omitted, the system's default locale is used. It can also be passed in the request body.

---

## Authentication

Every request must include a Bearer token:

```
Authorization: Bearer {api_key}
```

Each CMS generates and stores its own API key. The key must be validated on every request.

**Required error responses:**

| Status | Condition         | Response body                        |
|--------|-------------------|--------------------------------------|
| `401`  | Missing token     | `{ "error": "API key required." }`   |
| `401`  | Invalid token     | `{ "error": "Invalid API key." }`    |

---

## Request Format

Every request body must contain:

| Parameter  | Type   | Required | Description                     |
|------------|--------|----------|---------------------------------|
| `action`   | string | yes      | Operation to perform            |
| `resource` | string | yes      | Target resource type            |

All other fields are sent alongside `action` and `resource` in the same request body.

**Content types:**
- `application/json` for data operations
- `multipart/form-data` for file uploads

---

## Actions & Resources Reference

| resource      | action         | extra params                                          | description                |
|---------------|----------------|-------------------------------------------------------|----------------------------|
| `categories`  | `list`         | -                                                     | List all categories        |
| `categories`  | `create`       | category fields                                       | Create a category          |
| `categories`  | `update`       | `category_id` + fields to change                      | Update/rename a category   |
| `posts`       | `list`         | `status`, `category_id`, `locale`, `per_page`, `page` | List posts (filterable) |
| `posts`       | `show`         | `post_id`                                             | Get single post with all related data |
| `posts`       | `create`       | post fields + `sections`, `faqs`, `howto_steps` arrays | Create post with all data |
| `posts`       | `update`       | `post_id` + fields to change                          | Partial update             |
| `posts`       | `delete`       | `post_id`                                             | Delete post                |
| `posts`       | `upload-cover` | `post_id` + `cover_desktop` and/or `cover_mobile` files | Upload cover images     |
| `faqs`        | `list`         | `post_id`                                             | List FAQs for a post       |
| `faqs`        | `show`         | `faq_id`                                              | Get single FAQ             |
| `faqs`        | `create`       | `post_id` + FAQ fields                                | Add FAQ to post            |
| `faqs`        | `update`       | `faq_id` + fields to change                           | Update FAQ                 |
| `faqs`        | `delete`       | `faq_id`                                              | Delete FAQ                 |
| `howto-steps` | `list`         | `post_id`                                             | List steps for a post      |
| `howto-steps` | `show`         | `step_id`                                             | Get single step            |
| `howto-steps` | `create`       | `post_id` + step fields                               | Add step to post           |
| `howto-steps` | `update`       | `step_id` + fields to change                          | Update step                |
| `howto-steps` | `delete`       | `step_id`                                             | Delete step                |
| `media`       | `upload`       | `image` file                                          | Upload image, return path  |

---

## Field Definitions

### Category Fields

| Field        | Type    | Required | Max Length | Description                                    |
|--------------|---------|----------|-----------|------------------------------------------------|
| `title`      | string  | yes      | 255       | Category name                                  |
| `slug`       | string  | no       | 255       | URL slug (auto-generated from title if omitted)|
| `menu_title` | string  | no       | 255       | Short title for navigation menus               |
| `description`| string  | no       | unlimited | Category description                           |
| `meta_title` | string  | no       | 255       | SEO title                                      |
| `status`     | string  | no       | -         | `active` (default) or `inactive`               |
| `sort_order` | integer | no       | -         | Display order (default: 0)                     |

### Post Fields

| Field              | Type    | Required | Max Length | Description                                      |
|--------------------|---------|----------|-----------|--------------------------------------------------|
| `title`            | string  | yes      | 255       | Post title                                       |
| `slug`             | string  | no       | 255       | URL slug (auto-generated from title if omitted)  |
| `blog_category_id` | integer | yes      | -         | Category ID (fetch categories first to get IDs)  |
| `status`           | integer | yes      | -         | `0` = draft, `1` = published, `2` = archived    |
| `locale`           | string  | no       | 10        | Content language code, e.g. `lt`, `en` (defaults to system locale) |
| `excerpt`          | string  | no       | unlimited | Short summary text                               |
| `content`          | string  | no       | unlimited | Full blog post HTML content                      |
| `is_featured`      | boolean | no       | -         | Featured flag (default: false)                   |
| `published_at`     | string  | no       | -         | ISO 8601 date (e.g. `2026-04-15`)                |
| `meta_title`       | string  | no       | 255       | SEO title                                        |
| `meta_description` | string  | no       | 500       | SEO description                                  |
| `howto_title`      | string  | no       | 255       | HowTo structured data title                      |
| `howto_description`| string  | no       | unlimited | HowTo structured data description                |
| `faq_title`        | string  | no       | 255       | FAQ section heading                              |

### Section Fields (nested array: `sections`)

Content sections represent tabbed or segmented content blocks within a post.

| Field        | Type    | Required | Max Length | Description                    |
|--------------|---------|----------|-----------|--------------------------------|
| `tab_id`     | string  | yes      | 255       | Unique section identifier      |
| `title`      | string  | no       | 255       | Section heading                |
| `content`    | string  | yes      | unlimited | HTML content body              |
| `sort_order` | integer | no       | -         | Display order (default: 0)     |

### FAQ Fields (nested array: `faqs`)

FAQ entries for structured data (FAQPage schema) and display.

| Field        | Type    | Required | Max Length | Description              |
|--------------|---------|----------|-----------|--------------------------|
| `question`   | string  | yes      | 500       | FAQ question             |
| `answer`     | string  | yes      | unlimited | FAQ answer               |
| `sort_order` | integer | no       | -         | Display order (default: 0) |

### HowTo Step Fields (nested array: `howto_steps`)

Steps for structured data (HowTo schema) and display.

| Field        | Type    | Required | Max Length | Description                                |
|--------------|---------|----------|-----------|----------------------------------------------|
| `title`      | string  | yes      | 500       | Step title                                   |
| `content`    | string  | no       | unlimited | Step description                             |
| `image`      | string  | no       | 500       | Image path (from `media` > `upload` action)  |
| `tip`        | string  | no       | unlimited | Optional tip text                            |
| `sort_order` | integer | no       | -         | Display order (default: 0)                   |

### Cover Image Fields (multipart upload)

| Field            | Type | Required | Max Size | Description         |
|------------------|------|----------|----------|---------------------|
| `cover_desktop`  | file | no*      | 5 MB     | Desktop cover image |
| `cover_mobile`   | file | no*      | 5 MB     | Mobile cover image  |

*At least one is required. Replaces any existing cover for that type.

### Content Image Field (multipart upload)

| Field   | Type | Required | Max Size | Description                            |
|---------|------|----------|----------|----------------------------------------|
| `image` | file | yes      | 10 MB    | Image file (jpg, png, gif, webp, etc.) |

---

## Response Formats

All responses are JSON.

### Success: Single post

```json
{
  "data": { ... },
  "url": "https://example.com/category-slug/post-slug"
}
```

### Success: List

```json
{
  "data": [ ... ]
}
```

### Success: Paginated list (posts)

```json
{
  "current_page": 1,
  "data": [ ... ],
  "per_page": 15,
  "total": 42,
  "last_page": 3,
  "next_page_url": "/api/gateway?action=list&resource=posts&page=2",
  "prev_page_url": null
}
```

### Success: Media upload

```json
{
  "data": {
    "path": "/storage/blog-images/6626a1b2e3f4a_photo.jpg",
    "url": "https://example.com/storage/blog-images/6626a1b2e3f4a_photo.jpg"
  }
}
```

The `path` is a relative server path for use in post content. The `url` is the full public URL.

### Success: Cover upload

```json
{
  "data": {
    "cover_desktop_url": "https://example.com/storage/covers/desktop.jpg",
    "cover_mobile_url": "https://example.com/storage/covers/mobile.jpg"
  }
}
```

### Success: Delete

```json
{ "message": "Post deleted." }
```

### Error: Validation (422)

```json
{
  "message": "The title field is required.",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

### Error: Not found (404)

```json
{ "error": "Resource not found." }
```

### Error: Bad request (422)

When `action` or `resource` is missing or invalid:

```json
{
  "error": "Parameters \"action\" and \"resource\" are required.",
  "available_resources": ["categories", "posts", "faqs", "howto-steps", "media"],
  "available_actions": ["list", "show", "create", "update", "delete", "upload"]
}
```

---

## Update Behavior

When updating a post (`action: update`, `resource: posts`):

- **Post fields**: Only fields present in the request are updated (partial update).
- **Nested arrays** (`sections`, `faqs`, `howto_steps`):
  - If the key is **present**, all existing records are **replaced** with the provided array.
  - Send `[]` to clear all records for that relation.
  - If the key is **omitted**, existing records are left unchanged.

This means you can update just the title without touching FAQs, or replace all FAQs without touching sections.

---

## Examples

### List categories

```json
{
  "action": "list",
  "resource": "categories"
}
```

### Create a category

```json
{
  "action": "create",
  "resource": "categories",
  "title": "Receptai",
  "slug": "receptai",
  "status": "active"
}
```

### Update/rename a category

```json
{
  "action": "update",
  "resource": "categories",
  "category_id": 2,
  "title": "Alaus receptai",
  "slug": "alaus-receptai"
}
```

### List posts by category

```json
{
  "action": "list",
  "resource": "posts",
  "category_id": 2,
  "status": 1,
  "per_page": 10
}
```

### Create a full post with all related data

```json
{
  "action": "create",
  "resource": "posts",
  "title": "Kaip pasigaminti lageri namuose",
  "slug": "kaip-pasigaminti-lageri-namuose",
  "blog_category_id": 2,
  "status": 0,
  "excerpt": "Zingsnis po zingsnio lagerio gamybos vadovas.",
  "is_featured": false,
  "published_at": "2026-04-15",
  "meta_title": "Kaip pasigaminti lageri namuose",
  "meta_description": "Issamus lagerio gamybos vadovas: iranga, ingredientai ir procesas.",
  "howto_title": "Kaip pasigaminti lageri namuose",
  "howto_description": "Pilnas vadovas apie lagerio gamyba namuose.",
  "faq_title": "Lagerio gamybos DUK",
  "sections": [
    {
      "tab_id": "ivadas",
      "title": "Ivadas",
      "content": "<p>Lagerio gamyba reikalauja kantrybes...</p>",
      "sort_order": 0
    },
    {
      "tab_id": "procesas",
      "title": "Gamybos procesas",
      "content": "<p>Pradekite nuo salyklo paruosimo...</p>",
      "sort_order": 1
    }
  ],
  "faqs": [
    {
      "question": "Kiek laiko trunka lagerio fermentacija?",
      "answer": "Paprastai 4-8 savaites, iskaitant lageravima.",
      "sort_order": 0
    }
  ],
  "howto_steps": [
    {
      "title": "Paruoskite salykla",
      "content": "Sumalte salykla ir pamatuokite grudu kieki.",
      "image": "/storage/blog-images/6626a1b2e3f4a_salyklas.jpg",
      "tip": "Naudokite grudu maluneli tolygiam sumalimui.",
      "sort_order": 0
    }
  ]
}
```

### Update a post (partial)

```json
{
  "action": "update",
  "resource": "posts",
  "post_id": 5,
  "status": 1,
  "published_at": "2026-04-15T10:00:00Z"
}
```

### Update a single FAQ

```json
{
  "action": "update",
  "resource": "faqs",
  "faq_id": 12,
  "answer": "Updated answer text."
}
```

### Delete a post

```json
{
  "action": "delete",
  "resource": "posts",
  "post_id": 5
}
```

### Upload a content image (multipart/form-data)

```
action=upload
resource=media
image=@photo.jpg
```

### Upload cover images (multipart/form-data)

```
action=upload-cover
resource=posts
post_id=5
cover_desktop=@desktop.jpg
cover_mobile=@mobile.jpg
```

---

## Standard Workflow

The content generation platform follows this sequence to publish a post:

```
Step 1:  action=list       resource=categories              Get categories and their IDs
Step 2:  action=create     resource=categories              Create category if missing (returns ID)
Step 3:  action=upload     resource=media                   Upload content images (repeat)
Step 4:  action=create     resource=posts                   Create post with sections, FAQs, HowTo steps
Step 5:  action=upload-cover resource=posts  post_id={id}   Upload cover images
Step 6:  action=update     resource=posts   post_id={id}    Set status=published
```

To update existing content:

```
Step 1:  action=list       resource=posts   category_id=X   Find the post
Step 2:  action=update     resource=posts   post_id={id}    Update fields / replace nested data
   or:   action=update     resource=faqs    faq_id={id}     Update single FAQ
   or:   action=update     resource=howto-steps step_id={id} Update single step
```

---

## Implementation Requirements

1. Expose a single `POST /api/gateway` endpoint that reads `action` + `resource` and routes internally.
2. Store an encrypted API key. Validate the `Authorization: Bearer` token on every request.
3. Support category CRUD -- the caller will fetch existing categories first and pass the correct `blog_category_id` when creating posts.
4. Accept `slug` as an optional field. If not provided, auto-generate a URL-safe slug from `title`. Ensure uniqueness.
5. Handle file uploads, store in the platform's media system, return accessible paths and URLs.
6. Store `sections`, `faqs`, and `howto_steps` as related data to the post.
7. Enforce field types, max lengths, and required fields as defined above. Return `422` with the documented error format on validation failure.
