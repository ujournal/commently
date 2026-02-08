<?php

namespace Tests\Unit\SortByLikes;

use Flarum\Filter\FilterState;
use Flarum\Query\QueryCriteria;
use Flarum\User\User;
use Illuminate\Database\Query\Builder;
use Mockery;
use PHPUnit\Framework\TestCase;
use UJournal\SortByLikes\Filter\ApplyHotSortMutator;

class ApplyHotSortMutatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_does_nothing_when_sort_is_not_hot(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldNotReceive('reorder');
        $query->shouldNotReceive('orderByRaw');

        $filterState = Mockery::mock(FilterState::class);
        $filterState->shouldReceive('getQuery')->andReturn($query);

        $actor = Mockery::mock(User::class);
        $criteria = new QueryCriteria($actor, [], ['created_at' => 'desc']);
        $mutator = new ApplyHotSortMutator();

        $mutator($filterState, $criteria);
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function it_does_nothing_when_sort_is_array_with_multiple_fields(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldNotReceive('reorder');

        $filterState = Mockery::mock(FilterState::class);
        $filterState->shouldReceive('getQuery')->andReturn($query);

        $actor = Mockery::mock(User::class);
        $criteria = new QueryCriteria($actor, [], ['created_at' => 'desc', 'id' => 'asc']);
        $mutator = new ApplyHotSortMutator();

        $mutator($filterState, $criteria);
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function it_applies_hot_sort_for_mysql_when_sort_is_hot_desc(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('reorder')->once()->andReturnSelf();
        $query->shouldReceive('orderByRaw')->once()->with(Mockery::on(function ($sql) {
            return str_contains($sql, 'DESC')
                && str_contains($sql, 'like_count')
                && str_contains($sql, 'comment_count')
                && str_contains($sql, 'participant_count');
        }))->andReturnSelf();

        $connection = Mockery::mock();
        $connection->shouldReceive('getDriverName')->andReturn('mysql');

        $query->shouldReceive('getConnection')->andReturn($connection);

        $filterState = Mockery::mock(FilterState::class);
        $filterState->shouldReceive('getQuery')->andReturn($query);

        $actor = Mockery::mock(User::class);
        $criteria = new QueryCriteria($actor, [], ['hot' => 'desc']);
        $mutator = new ApplyHotSortMutator();

        $mutator($filterState, $criteria);
        $this->addToAssertionCount(2);
    }

    /** @test */
    public function it_applies_hot_sort_for_sqlite_when_sort_is_hot_asc(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('reorder')->once()->andReturnSelf();
        $query->shouldReceive('orderByRaw')->once()->with(Mockery::on(function ($sql) {
            return str_contains($sql, 'ASC')
                && str_contains($sql, 'julianday');
        }))->andReturnSelf();

        $connection = Mockery::mock();
        $connection->shouldReceive('getDriverName')->andReturn('sqlite');

        $query->shouldReceive('getConnection')->andReturn($connection);

        $filterState = Mockery::mock(FilterState::class);
        $filterState->shouldReceive('getQuery')->andReturn($query);

        $actor = Mockery::mock(User::class);
        $criteria = new QueryCriteria($actor, [], ['hot' => 'asc']);
        $mutator = new ApplyHotSortMutator();

        $mutator($filterState, $criteria);
        $this->addToAssertionCount(2);
    }
}
