<head>
    <link rel='stylesheet' id='billy-penn-styles-css' href='/wp-content/themes/billy-penn/assets/dist/css/theme.css' type='text/css' media='all' />
    <link rel="stylesheet" href="/styleguide/src/highlightjs/styles/github.css">
    <script src="/styleguide/src/highlightjs/highlight.pack.js"></script>
    <script>hljs.initHighlightingOnLoad();</script>
</head>

<body style="background-color: #fff;">

<div class="content-wrapper">
<main class="c-main columns s-content">

{% markdown %}

# Spirited Media's Styleguide

**Note that our styles here don't affect the WordPress admin area or any other
part of the site requiring a logged in user.** Components used in the WordPress
admin area won't be covered in this styleguide, meaning that the live usage of
components in the admin area also won't be covered.

Also note that this styleguide can not be 100% accurate. Any omissions or other
mistakes should be reported to the product team and a ticket should be opened to
address the issue.

## Core Components

- [Buttons](buttons.php) / [Buttons V2](buttons-v2.php)
- [Forms](forms.php) / [Forms V2](forms-v2.php)
- [Headers](headers.php)
- [Horizontal Rules](horizontal-rules.php)
- [Icons](icons.php)
- [Input Groups (Form Input + Button)](input-groups.php)
- [Modals](modals.php)
- [Pagination](pagination.php)
- [Post Content: Common HTML](common-html.php)
- [Post Content: Post Elements](post-elements.php)
- [Post Content: Images](images.php)
- [Post Headers](post-headers.php)
- [Single Entity: Cluster List](single-entity-cluster-list.php)
- [Stream Items](stream-items.php)
- [Tables](tables.php)
- [User Card / User Grid](user-card.php)

Also present on each page are:

- Site header
- Newsletter signup modal
- Search
- Spotlight – might be hidden depending on current site settings
- Site footer

## Utilities

These styles are for development convenience -- they probably should never change.

- [Utilities](utilities.php)
- [Media Object](media-object.php)

## Other Components


The following components are not yet represented in the styleguide. However,
they should be considered during a major redesign as they are actively used on
the sites.

- Daily Instagram widget in rail and single newsletters
- Donation form
- Events in stream
- Events as single posts
- Who's Next single post
- Factcheck statements/quotes
- Recent content widget
- Slots / sponsored posts / sponsored stream items / sponsored newsletters /
  sponsored events
- Recent video widget


{% endmarkdown %}

</main>
</div>
</body>
