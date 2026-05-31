<?php

use Keepsuit\LaravelTemporal\Support\ScheduleMemo;
use Temporal\DataConverter\EncodedCollection;

it('builds ownership markers carrying the content hash', function () {
    expect(ScheduleMemo::markers('abc123'))
        ->toBe(['_lt_managed' => true, '_lt_hash' => 'abc123']);
});

it('reads the managed flag and hash from a memo', function () {
    $memo = EncodedCollection::fromValues(ScheduleMemo::markers('h1'));

    expect(ScheduleMemo::isManaged($memo))->toBeTrue();
    expect(ScheduleMemo::hash($memo))->toBe('h1');
});

it('treats foreign memos as unmanaged with no hash', function () {
    $memo = EncodedCollection::fromValues(['team' => 'data']);

    expect(ScheduleMemo::isManaged($memo))->toBeFalse();
    expect(ScheduleMemo::hash($memo))->toBeNull();
});
