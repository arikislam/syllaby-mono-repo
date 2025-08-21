
# Social Media Content Generator
## Overview
Social Media Content Generator is an application built using Laravel 9, designed to generate social media content for both written, social and video content. It helps users to quickly and easily create content for the most popular social media platforms, including Facebook, Twitter, Instagram, YouTube, and more. This application provides a user-friendly interface and powerful features that allow users to create custom content for their social media accounts.

## Features
- Find ideas of what content to build
- Schedule content onto a calendar
- Create individual content for vertical videos, articles, and social media posts

## Installation
1. Install the necessary software packages (PHP, MySQL, Apache, Laravel, Node.js).
2. Download the Social Media Content Generator repository from GitHub.
3. Create a new MySQL database and configure `.env` to connect to your database.
4. Install the necessary dependencies using Composer. `composer install`
5. Run the migration scripts to create the necessary tables in your database. `php artisan migrate`
6. Configure [stripe subscriptions](/README-STRIPE.md)
7. Configure SEMRush API Key
8. Run the seeders
    > php artisan db:seed 
9. Configure Custom API Key
10.Start the application server.

## Usage and git flow
> The goal of this part is to avoid direct pushes to the `master` branch. When working on new features, fixing issues, or improving something, always create a new branch from the `master` branch.

- For developing a new feature, the branch name should start with `feat/<branch-name>`.
- For improving a feature, the branch name should start with `imp/<branch-name>`.
- For bug fixes, the branch name should start with `fix/<branch-name>`.
- For production fixes, the branch name should start with `hot-fix/<branch-name>`.

>After completing tasks in the intended branch, create a pull request to the `master` branch. Then, switch to the `staging` branch on your local machine and pull from the `origin/staging` branch. Then merge your branch and push the `staging` branch to the remote repository. It will update the dev server with git workflow.

### Git Process

1. Create a feature branch for each feature, improvement, bug fix or production fix. This will help keep the master branch clean and organized.
2. Make sure to include descriptive commit messages, as this will help other contributors understand what changes were made. You can insert your recent code into ChatGPT and ask it to make a 2 sentence summary.
3. In the future, make sure to include tests, automated or manual, when developing a feature. This will help ensure that the feature works as intended.
4. Pull from the origin/master branch prior to pushing any local changes, to ensure that all changes are up-to-date.
5. Always review and discuss any changes prior to merging a branch into the master branch with Ariq.
6. Use tags to help organize branches, such as feature, bug, fix, etc.
7. Always use pull requests (PR) to ensure code quality and to provide a way to discuss changes.
8. Do code reviews with Ariq ensure that all code is of high quality and meets the standards set by the development team.

### Readme Best Practices
- Keep the file up to date: Ensure that the README.md file is updated with the latest information about your project.
- Use descriptive titles: Use descriptive titles and headings in your README.md file to help organize the information and make it easier to read.
- Use simple language: Avoid technical jargon and long-winded explanations. Keep your language simple and easy to understand.
- Include installation instructions: Include step-by-step instructions on how to install and use your project.
- Include screenshots: Include screenshots of your project in action to give readers a better understanding of how it works.
- Link to external resources: If your project relies on external resources, link to them in the README.md file.
- Use markdown: Format your README.md using markdown to make it easier to read.
- Structure documentation clearly and logically, using headings and subsections to organize information.
- Include examples and screenshots to illustrate concepts and processes.
- Link to related topics and resources to provide further detail and context.
