<?php
include 'include.php';
styleguide_header();
?>
<div class="content-wrapper row">
    <main class="c-main columns large-8 js-main" role="main">
        <header class="c-main__header">
            <h1 class="c-main__title">Input Groups</h1>
        </header>

        <div class="input-group">
            <span class="input-group__addon" id="basic-addon1">@</span>
            <input type="text" class="form-input" placeholder="Username" aria-describedby="basic-addon1">
        </div>
            <br>
        <div class="input-group">
            <input type="text" class="form-input" placeholder="Recipient's username" aria-describedby="basic-addon2">
            <span class="input-group__addon" id="basic-addon2">@example.com</span>
        </div>
            <br>
        <label for="basic-url">Your vanity URL</label>
        <div class="input-group">
            <span class="input-group__addon" id="basic-addon3">https://example.com/users/</span>
            <input type="text" class="form-input" id="basic-url" aria-describedby="basic-addon3">
        </div>
        <br>
        <div class="input-group">
            <span class="input-group__addon">$</span>
            <input type="text" class="form-input" aria-label="Amount (to the nearest dollar)">
            <span class="input-group__addon">.00</span>
        </div>
        <br>
        <div class="input-group">
            <span class="input-group__addon">$</span>
            <span class="input-group__addon">0.00</span>
            <input type="text" class="form-input" aria-label="Amount (to the nearest dollar)">
        </div>

        <h4>Button Addons</h4>

        <div class="row">
            <div class="columns large-6">
                <div class="input-group">
                    <span class="input-group__btn">
                        <button class="btn btn--primary" type="button">Go!</button>
                    </span>
                    <input type="text" class="form-input" placeholder="Search for...">
                </div>
            </div>
            <div class="columns large-6">
                <div class="input-group">
                    <input type="text" class="form-input" placeholder="Search for...">
                    <span class="input-group__btn">
                        <button class="btn btn--primary" type="button">Go!</button>
                    </span>
                </div>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="columns large-6">
                <div class="input-group">
                    <span class="input-group__btn">
                        <button class="btn btn--primary" type="button">Hate it</button>
                    </span>
                    <input type="text" class="form-input" placeholder="Product name">
                    <span class="input-group__btn">
                        <button class="btn btn--primary" type="button">Love it</button>
                    </span>
                </div>
            </div>
        </div>

        <h4>Sizing</h4>

        <div class="input-group input-group--lg">
            <span class="input-group__addon" id="sizing-addon1">@</span>
            <input type="text" class="form-input" placeholder="Username" aria-describedby="sizing-addon1">
        </div>
        <br>
        <div class="input-group input-group--sm">
            <span class="input-group__addon" id="sizing-addon2">@</span>
            <input type="text" class="form-input" placeholder="Username" aria-describedby="sizing-addon2">
        </div>
        <br>
        <div class="input-group input-group--lg">
            <span class="input-group__btn">
                <button class="btn btn--primary" type="button">Go!</button>
            </span>
            <input type="text" class="form-input" placeholder="Username" aria-describedby="sizing-addon1">
        </div>
        <br>
        <div class="input-group input-group--sm">
            <span class="input-group__btn">
                <button class="btn btn--primary" type="button">Go!</button>
            </span>
            <input type="text" class="form-input" placeholder="Username" aria-describedby="sizing-addon2">
        </div>

        <h4>Checkboxes and Radio Addons</h4>

        <div class="row">
            <div class="columns large-6">
                <div class="input-group">
                    <span class="input-group__addon">
                        <input type="checkbox" aria-label="Checkbox for following text input">
                    </span>
                    <input type="text" class="form-input" aria-label="Text input with checkbox">
                </div>
            </div>
            <div class="columns large-6">
                <div class="input-group">
                    <span class="input-group__addon">
                        <input type="radio" aria-label="Radio button for following text input">
                    </span>
                    <input type="text" class="form-input" aria-label="Text input with radio button">
                </div>
            </div>
        </div>

    </main>
    <aside class="rail columns large-4"></aside>
</div>

<?php
styleguide_footer();
