<?php
include 'include.php';
styleguide_header();
?>

<style>
    .form-input + .form-input {
        margin-top: 0.5rem;
    }
</style>

<div class="content-wrapper row">
    <main class="c-main columns large-8 js-main" role="main">
        <header class="c-main__header">
            <h1 class="c-main__title">Forms</h1>
        </header>

        <form>
            <div class="form-group">
                <label>
                    Email address
                    <input type="email" class="form-input" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter email">
                </label>
                <small id="emailHelp" class="form-group__text text-muted">We'll never share your email with anyone else.</small>
            </div>
            <div class="form-group">
                <label for="exampleInputPassword1">
                    Password
                    <input type="password" class="form-input" id="exampleInputPassword1" placeholder="Password">
                </label>
            </div>
            <div class="form-group">
                <label for="exampleSelect1">Example select</label>
                <select class="form-input" id="exampleSelect1">
                  <option>1</option>
                  <option>2</option>
                  <option>3</option>
                  <option>4</option>
                  <option>5</option>
                </select>
            </div>
            <div class="form-group">
                <label for="exampleSelect2">Example multiple select</label>
                <select multiple class="form-input" id="exampleSelect2">
                  <option>1</option>
                  <option>2</option>
                  <option>3</option>
                  <option>4</option>
                  <option>5</option>
                </select>
            </div>
            <div class="form-group">
                <label for="exampleTextarea">Example textarea</label>
                <textarea class="form-input" id="exampleTextarea" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="exampleInputFile">File input</label>
                <input type="file" class="form-input--file" id="exampleInputFile" aria-describedby="fileHelp">
                <small id="fileHelp" class="form-group__text text-muted">This is some placeholder block-level help text for the above input. It's a bit lighter and easily wraps to a new line.</small>
            </div>
            <fieldset class="form-group">
                <legend class="form-group__legend">Radio buttons</legend>
                <div class="form-check">
                  <label class="form-check__label">
                    <input type="radio" class="form-check__input" name="optionsRadios" id="optionsRadios1" value="option1" checked>
                    Option one is this and that&mdash;be sure to include why it's great
                  </label>
                </div>
                <div class="form-check">
                <label class="form-check__label">
                    <input type="radio" class="form-check__input" name="optionsRadios" id="optionsRadios2" value="option2">
                    Option two can be something else and selecting it will deselect option one
                  </label>
                </div>
                <div class="form-check disabled">
                <label class="form-check__label">
                    <input type="radio" class="form-check__input" name="optionsRadios" id="optionsRadios3" value="option3" disabled>
                    Option three is disabled
                  </label>
                </div>
            </fieldset>
            <div class="form-check">
                <label class="form-check__label">
                  <input type="checkbox" class="form-check__input">
                  Check me out
                </label>
            </div>
            <button type="submit" class="btn btn--primary">Submit</button>
        </form>

        <h4>Disabled State</h4>

        <form>
            <fieldset disabled>
                <div class="form-group">
                  <label for="disabledTextInput">Disabled input</label>
                  <input type="text" id="disabledTextInput" class="form-input" placeholder="Disabled input">
                </div>
                <div class="form-group">
                  <label for="disabledSelect">Disabled select menu</label>
                  <select id="disabledSelect" class="form-input">
                    <option>Disabled select</option>
                  </select>
                </div>
                <div class="checkbox">
                  <label>
                    <input class="form-check" type="checkbox"> Can't check this
                  </label>
                </div>
                <button type="submit" class="btn btn--primary">Submit</button>
            </fieldset>
        </form>

        <h4>Readonly Field</h4>

        <input class="form-input" type="text" placeholder="Readonly input here…" readonly>

        <h4>Field Sizing</h4>

        <input class="form-input form-input--lg" type="text" placeholder=".form-input--lg">
        <input class="form-input" type="text" placeholder="Default input">
        <input class="form-input form-input--sm" type="text" placeholder=".form-input--sm">

        <select class="form-input form-input--lg">
            <option>Large select</option>
        </select>
        <select class="form-input">
            <option>Default select</option>
        </select>
        <select class="form-input form-input--sm">
            <option>Small select</option>
        </select>


        <h4>Validation</h4>

        <form>
            <div class="row">
                <div class="columns medium-6">
                    <label for="validationServer01">First name</label>
                    <input type="text" class="form-input is-valid" id="validationServer01" placeholder="First name" value="Billy" required>
                </div>
                <div class="columns medium-6">
                    <label for="validationServer02">Last name</label>
                    <input type="text" class="form-input is-valid" id="validationServer02" placeholder="Last name" value="Penn" required>
                </div>
            </div>
            <div class="row">
                <div class="columns medium-6">
                    <label for="validationServer03">City</label>
                    <input type="text" class="form-input is-invalid" id="validationServer03" placeholder="City" required>
                    <div class="invalid-feedback">
                        Please provide a valid city.
                    </div>
                </div>
                <div class="columns medium-3">
                    <label for="validationServer04">State</label>
                    <input type="text" class="form-input is-invalid" id="validationServer04" placeholder="State" required>
                    <div class="invalid-feedback">
                        Please provide a valid state.
                    </div>
                </div>
                <div class="columns medium-3">
                    <label for="validationServer05">Zip</label>
                    <input type="text" class="form-input is-invalid" id="validationServer05" placeholder="Zip" required>
                    <div class="invalid-feedback">
                        Please provide a valid zip.
                    </div>
                </div>
            </div>

            <button class="btn btn-primary" type="submit">Submit form</button>
        </form>

    </main>
    <aside class="rail columns large-4"></aside>
</div>

<?php
styleguide_footer();