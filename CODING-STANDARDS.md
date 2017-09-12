# Coding Standards

## Sass

### Units

All absolute values should be in pixels and passed to the `rem()` function.

Pixels are our atomic unit of measurement for authoring in Sass. To preserve vertical rhythm, vertical spacing like `margin` and `padding` should be used in multiples of `16px` and `8px`, or `4px` at the minimum.

Rems are the preferred units for compiled CSS due to their relative sizing based on the `<html>` element. If a visitor has specified their desired font size in their browser (aka thy set the default font size to 36px, not 16px) our CSS should respect that and scale proportionatly.

```
// Bad
.foo {
  padding: 11px 1em 1.5rem;
}

// Good
.foo {
  padding: rem( 12px ) rem( 16px ) rem( 24px );
}
```

### Variables

Variables should generally only be used to tie values together. Be wary of tightly coupling values and over abstracting.

Variables should be defined at the top of a file where they are used. If the variable is to be used across multiple files it should go in `/wp-content/themes/pedestal/assets/scss/base/theme/_settings.scss`

Colors and font sizes should always be variables. We have a limited scope of font sizes defined and we should aim to use one of those sizes whenever possible.

```
// Bad
.foo {
  font-size: rem( 24px );
  color: #4fa8e0;
}

// Good
.foo {
  font-size: $h4-font-size;
  color: $twitter-blue;
}
```

### Media Queries

Instead of writing inline media queries, we should write media queries as big blocks at the bottom of the file or at the bottom of a subsection of the file. Big blocks at the bottom of sections are easier to comprehend and follow. Writing the same media query over and over becomes tedious.

We don't use defined common break points. Each break point should be dictated by the needs of the component and used to adjust styling at the size in which the component "breaks".

Media queries should use pixel values converted to em units with `em()`.

```
//
// Bad
//

.foo {
  padding-bottom: rem( 16px );

  @media( min-width: $small-medium-up-down ) {
    padding-bottom: rem( 8px );
  }
}

.bar {
  font-size: $h3-font-size;

  @media( min-width: $small-medium-up-down ) {
    font-size: $h4-font-size;
  }
}


//
// Good
//

.foo {
  padding-bottom: rem( 16px );
}

.bar {
  font-size: $h3-font-size;
}

@media( min-width: em( 640px ) ) {
  .foo {
    padding-bottom: rem( 8px );
  }

  .bar {
    font-size: $h4-font-size;
  }
}
```


### Comments

Use `//` for comment blocks. Use [Sassdoc](http://sassdoc.com/annotations/) for function and mixin annotation. If you have large chunks of code to comment out `/* ... */` is fine too but use sparingly.

For dividing your file into sections, use dividers like so:

```
... end of previous section, followed by five empty lines ...





//
// This is a subsection divider
// ==========================================================================
// If you need to further explain what's going on in this particular section
// as a whole, you can do that in this space right here. The divider should
// be 78 characters wide. The divider should be followed by two empty lines.


... more code here ...
```

If you're using Atom, [these snippets](https://atom.io/packages/atom-idiomatic-comments-css-snippets) can help.

```
/* This is incorrect */
.some-class {
  display: block;
}

/*
This is also incorrect
.an-unwanted-class {
  display: block;
}
*/


//
// If there's several related blocks you're trying to write a comment
// about use this format.
//

// This is correct
.some-class {
  display: block;
}

/// This is correct Sassdoc formatting
///
/// @param {string} $bar
/// @return {string}
@function foo($bar) {
  @return $bar;
}
```

## PHP

### Comments

Use `//` for comment blocks. Use [phpDocumentor](https://www.phpdoc.org/) for annotating functions, classes, properties, etc. If you have large chunks of code to comment out `/* ... */` is fine too but use sparingly.

```
/* This is incorrect */
function foo( $bar ) {
  return $bar;
}

/*
This is also incorrect
function foo( $bar ) {
  return $bar;
}
*/

// This is incorrect -- use phpDoc
function foo( $bar ) {
  return $bar;
}

// This is correct
// function eh( $meh ) {
//   return $meh;
// }

/**
 * This is correct too
 *
 * It's a docblock.
 *
 * @param string blah
 * @return string
 */
function yar( $blah ) {
  return blah;
}
```
