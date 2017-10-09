<?php
include 'include.php';
$checklist_root = 'https://github.com/spiritedmedia/spiritedmedia/tree/master/qa/checklists';
?>

<h1>QA Tools and Resources</h1>
<p>Here are some tools to make QAing easier.</p>
<ul>
    <li><a href="ad-checker.php">Ad checker</a> - Count the number of DFP ad postions and compare against the number of expected positions for each type of template.</li>
    <li><a href="email-signup-checker.php">Email Signup checker</a> - Make sure it is possible to signup for emails.</li>
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
