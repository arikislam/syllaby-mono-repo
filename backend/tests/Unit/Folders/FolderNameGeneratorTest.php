<?php

use App\Syllaby\Users\User;
use App\Syllaby\Folders\Folder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Syllaby\Folders\Generators\NumberAppendingFolderNameGenerator;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->generator = new NumberAppendingFolderNameGenerator;
});

it('generates a unique name when no folders exist', function () {
    $name = $this->generator->generate($this->user, 'New Folder');

    expect($name)->toBe('New Folder');
});

it('appends a number when a folder with the same name exists', function () {
    Folder::factory()->recycle($this->user)->create(['name' => 'Existing Folder']);

    $name = $this->generator->generate($this->user, 'Existing Folder');

    expect($name)->toBe('Existing Folder (1)');
});

it('increments the number for multiple existing folders', function () {
    Folder::factory()
        ->for($this->user)
        ->forEachSequence(['name' => 'Multiple Folder'], ['name' => 'Multiple Folder (1)'])
        ->create();

    $name = $this->generator->generate($this->user, 'Multiple Folder');

    expect($name)->toBe('Multiple Folder (2)');
});

it('handles folders with numbers already in parentheses', function () {
    Folder::factory()->for($this->user)->create(['name' => 'Numbered Folder (42)']);

    $name = $this->generator->generate($this->user, 'Numbered Folder (42)');

    expect($name)->toBe('Numbered Folder (42) (1)');
});

it('only considers folders belonging to the given user', function () {
    $other = User::factory()->create();

    Folder::factory()->recycle($other)->create(['name' => 'Other User Folder']);

    $name = $this->generator->generate($this->user, 'Other User Folder');

    expect($name)->toBe('Other User Folder');
});

it('handles special characters in folder names', function () {
    Folder::factory()->recycle($this->user)->create(['name' => 'Special @#$% Folder']);

    $name = $this->generator->generate($this->user, 'Special @#$% Folder');

    expect($name)->toBe('Special @#$% Folder (1)');
});
