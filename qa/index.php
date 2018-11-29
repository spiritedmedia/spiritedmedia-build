<?php
include 'include.php';
$checklist_root = 'https://github.com/spiritedmedia/spiritedmedia/tree/master/qa/checklists';
?>

<h1>QA Tools and Resources</h1>
<p>Here are some tools to make QAing easier.</p>
<ul>
    <li><a href="ad-checker.php">Ad checker</a> - Count the number of DFP ad postions and compare against the number of expected positions for each type of template.</li>
    <li><a href="email-signup-checker.php">Email Signup checker</a> - Make sure it is possible to signup for emails.</li>
    <li><a href="subscriber-cookie-checker.php">Subscriber Cookie checker</a> - Make sure subscriber cookies are getting the right data from MailChimp.</li>
    <li><a href="subscriber-cookie-tool.php">Subscriber Cookie tool</a> - Manually set individual subscriber cookie values for testing purposes.</li>
    <li><a href="side-by-side/">Side-by-side Comparison Tool</a> - Check a staging and production URL side by side. <a href="side-by-side/help.php">More Info</a></li>
</ul>

<h2>Checklists</h2>
<ul>
    <li><a href="<?php echo $checklist_root; ?>/checklist-post-deployment.md">Post Deployment QA</a></li>
    <li><a href="<?php echo $checklist_root; ?>/checklist-billypenn-visual-qa.md">Billy Penn Visual QA</a></li>
    <li><a href="<?php echo $checklist_root; ?>/checklist-billypenn-emails.md">Billy Penn Emails</a></li>
    <li><a href="<?php echo $checklist_root; ?>/checklist-embeds.md">Embeds</a></li>
    <li><a href="<?php echo $checklist_root; ?>/checklist-img-alignment.md">Image Alignment</a></li>
    <li><a href="<?php echo $checklist_root; ?>/checklist-img-metadata.md">Image Metadata</a></li>
</ul>

<h2><a href="http://bookmarklets.org/maker/" target="_blank">Bookmarklets</a></h2>
<p><strong>Google Analytics Event Checker</strong> - Activate the bookmarklet and hover over elements to see the event data in your Dev Tools console that would be sent to Google Analytics on click or form submission</p>

<p><a href="javascript:void%20function(){jQuery(document).ready(function(e){var%20a=location.search.indexOf(%22debug-ga%22);if(-1%3E=a){var%20o=%22%3F%22;location.search.indexOf(%22%3F%22)%3E=0%26%26(o=%22%26%22),alert(%22Click%20the%20bookmarklet%20again%20after%20the%20page%20reloads%22),window.location=window.location+o+%22debug-ga%22}e(%22body%22).on(%22mouseenter%22,%22a[data-ga-category]%22,function(a){e(this).trigger(%22click%22)}).on(%22mouseenter%22,%22form[data-ga-category]%22,function(a){e(this).trigger(%22submit%22),a.preventDefault()})})}();">Ped Event Checker</a> (Drag to your bookmark toolbar)</p>

<p>Raw Source</p>
<pre><code>
    jQuery(document).ready(function($) {
        // Make sure ?debug-ga flag is set
        var hasFlag = location.search.indexOf('debug-ga');
        if ( hasFlag <= -1 ) {
            var sep = '?';
            if ( location.search.indexOf('?') >= 0 ) {
                sep = '&';
            }
            alert('Click the bookmarklet again after the page reloads');
            window.location = window.location + sep + 'debug-ga';
        }

        $('body')
            .on('mouseenter', 'a[data-ga-category]', function(e) {
                $(this).trigger('click');
            })
            .on('mouseenter', 'form[data-ga-category]', function(e) {
                $(this).trigger('submit');
                e.preventDefault();
            });
    });
</code></pre>
