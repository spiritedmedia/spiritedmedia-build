# Contributing Guidelines

Please read this entire document before beginning work on Pedestal. Sorry to have to require this, but it's important to keep things moving smoothly. If anything seems like a particularly annoyingly tall order, feel free to contact @montchr with constructive criticisms.

## Formatting with GitHub Flavored Markdown

Please use [GitHub Flavored Markdown](https://help.github.com/articles/github-flavored-markdown/) whenever possible. This keeps issues, pull requests, comments, and other text documents readable.

Formatting is especially important when quoting large blocks of text, for example a conversation from Slack or an email.

Code must also be `formatted inline` with single backticks, or by using three backticks or indented for larger blocks of code. Refer to the GFM documentation (linked above) for more details.

## Commit Messages

Here's a model commit message:

```md
Capitalized, short (50 chars or less) summary

More detailed explanatory text, if necessary.  Wrap it to about 72
characters or so.  In some contexts, the first line is treated as the
subject of an email and the rest of the text as the body.  The blank
line separating the summary from the body is critical (unless you omit
the body entirely); tools like rebase can get confused if you run the
two together.

Write your commit message in the imperative: "Fix bug" and not "Fixed bug"
or "Fixes bug."  This convention matches up with commit messages generated
by commands like git merge and git revert.

Further paragraphs come after blank lines.

- Bullet points are okay, too

- Typically a hyphen or asterisk is used for the bullet, followed by a
  single space, with blank lines in between, but conventions vary here

- Use a hanging indent
```

These aren't just arbitrary rules, but are in fact [widely](http://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html) [considered](http://stackoverflow.com/questions/2290016/git-commit-messages-50-72-formatting) to be Git best practices.

The 50/72 character rule is especially important in making commit messages readable on GitHub and in `git log`.

## Issues

* Clear titles make everything much more manageable. For instance, "Twitter optimization" is better written as "Incorporate Twitter Card meta tags into header".
* Use in-description task lists to itemize components of the issue:

	```
	- [ ] Specific thing needing to be done
	- [ ] Another specific thing
	- [ ] Yet another thing
	```
	
* Be descriptive in your issue description. Feel free to rewrite / modify as needed.
* Poorly-defined issues, or ideas more broadly, shouldn't be filed as Github issues. These should be discussed with the product team at [product@billypenn.com](product@billypenn.com) or on Slack in `#product`.

## Pull Requests

Code is developed on feature branches. These will be based off `master`, beginning with the issue number the branch correspond to, then discriptively named following the number.

![](http://cl.ly/image/1P1c1J0f2G3o/Image%202015-06-17%20at%2011%3A15%3A07.png)

Pull requests are where we discuss the development in progress, and determine whether a given feature branch is ready for merging to master.

In general, issues are for discussing the problem at hand or a desired feature, while pull requests are for dicussing a possible solution or potential implementation of the new feature. 

Please create your pull request against the `master` branch as soon as you start work on a branch. When creating a pull request, please use a clear title and reference the issue in the pull request description:

![](https://cloud.githubusercontent.com/assets/36432/4596222/34b36b7a-50a1-11e4-999c-9f82c73e9925.png)

This makes it easy to see the history of pull requests for an issue:

![](https://cloud.githubusercontent.com/assets/36432/4596226/49d70db8-50a1-11e4-8e05-1ea4a2c7e7b8.png)

Issues should exist for pull requests as much as possible. This is why we begin new feature branches with an issue number. Issues are how we track a feature question from creation to closing.

If you are a Collaborator, please allow a core development team member to merge your pull request.

### TODOs and Pre-/Post-Deploy Requirements

Pull requests that involve multiple moving parts may benefit from a TODO checklist kept up to date on the opening comment.

![](http://cl.montchr.io/2w0f3R010S1W/Image%202016-04-01%20at%2013.45.47.png)

Because GitHub Issues doesn’t have any protection against overwriting other people’s changes when editing comments, we declare a maintainer for a particular PR (see above screenshot). This is usually the developer who opened the PR. **Only the PR maintainer may edit the checklist.**

Some PRs are more involved than others, requiring extra steps to be taken on the site before or after deployment. For these PRs, we add additional sections called “Pre-Deploy” and “Post-Deploy” and list the necessary actions step by step.

![](http://cl.montchr.io/1e2d331T1y0H/Image%202016-04-01%20at%2013.55.48.png)

## Labels

* **Scopes** — labels beginning with `scope:` — represent different components of the product. Each issue should have one or more scope labels assigned. Scope names (the part after the word "scope") should be concise, as they'll be used in commit messages (see above).
* **Tasks** — labels beginning with `task:` — define what type of work is involved, and who should be paying attention. For example, if an issue only requires developer attention, then it should be labelled `task:development`, but if both designers and developers should pay attention, then add both the `task:development` and the `task:design` labels.
* **Requests** — labels beginning with `request:` — these issues were specifically requested by the specified department, e.g. `request:sales` or `request:editorial`
* **Needs Clarification** — labels beginning with `clarify:` — these issues require more information before the product team can take action — they need clarification from the specified department e.g. `clarify:sales` or `clarify:editorial`
* Numbered labels represent state of completion for Huboard:
    * Every upcoming task/issue not in the active milestone should be in the backlog state.
    * Issues in the “Shipped” state should be closed by the product manager or acting issue tracker jockey on a regular basis.

## Coding Standards

### EditorConfig

Basic coding standards are enforced by [EditorConfig](http://editorconfig.org/). You will need to install the EditorConfig plugin for your text editor. EditorConfig will read the `.editorconfig` file in the project root.

- Indent PHP with 4 spaces
- Indent frontend assets with 2 spaces (`*.js, *.scss`)
- PHP only: Spaces between parentheses, e.g. `foo( $bar )` or `if ( $foo == 'bar' )`
- Use LF (Unix) line endings
- UTF-8 character encoding
- Trailing whitespace will be trimmed upon save
- A newline character will be inserted at the end of every file upon save

### PHP CodeSniffer + SCSSLint + JSHint

PHP, SCSS, and JS files are sniffed upon running `grunt` or `grunt build`. So try to run these before committing changes.

PHP CodeSniffer uses a slightly-modified version of the WordPress Coding Standards. Basically it's the same except we indent with spaces.

## Non-Feature Branches

The `master` branch is the main branch on GitHub, and it's the branch we'll be merging our feature branch PRs into.

**Do not commit directly to master.** Instead, your work should happen on feature branches, which will be merged into `master`. 

Sometimes, if a severe bug is discovered and needs immediate fixing, then we use hotfix branches which can be merged to master without having to worry about managing an issue. Hotfix branches begin with `hotfix-`.

**The `master` branch deploys automatically to http://dev.billypenn.com/ by way of DeployBot.**
