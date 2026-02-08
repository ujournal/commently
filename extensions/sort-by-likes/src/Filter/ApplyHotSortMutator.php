<?php

namespace Commently\SortByLikes\Filter;

use Flarum\Filter\FilterState;
use Flarum\Query\QueryCriteria;

/**
 * When sort=hot is requested, order discussions by a composite "hot" score
 * that balances popularity, activity, and recency.
 *
 * Engagement (numerator):
 * - Likes: sqrt(like_count + 1) — strong signal, dampened so a few mega-liked
 *   threads don’t dominate.
 * - Comments: weight × sqrt(comment_count + 1) — lively discussions with many
 *   replies rank higher.
 * - Participants: weight × sqrt(participant_count + 1) — threads where many
 *   different people post get a boost (conversation, not monologue).
 *
 * Time decay (denominator): (age_in_hours + 2)^gravity so newer and recently
 * active discussions rank higher.
 *
 * Result: hot = engagement / (age + 2)^gravity
 */
class ApplyHotSortMutator
{
    /** @var float Exponent for time decay (higher = newer posts favoured more). */
    private const GRAVITY = 1.5;

    /** @var float Weight for comment count (liveliness). */
    private const WEIGHT_COMMENTS = 0.5;

    /** @var float Weight for participant count (diversity of voices). */
    private const WEIGHT_PARTICIPANTS = 0.25;

    public function __invoke(FilterState $filterState, QueryCriteria $criteria): void
    {
        $sort = $criteria->sort;
        if (! is_array($sort) || count($sort) !== 1) {
            return;
        }

        $field = key($sort);
        $order = current($sort);
        if ($field !== 'hot' || ! in_array($order, ['asc', 'desc'], true)) {
            return;
        }

        $query = $filterState->getQuery();
        $driver = $query->getConnection()->getDriverName();
        $direction = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        $g = self::GRAVITY;
        $wc = self::WEIGHT_COMMENTS;
        $wp = self::WEIGHT_PARTICIPANTS;

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $age = "GREATEST(TIMESTAMPDIFF(HOUR, COALESCE(discussions.last_posted_at, discussions.created_at), NOW()), 0) + 2";
            $likes = "SQRT(COALESCE(discussions.like_count, 0) + 1)";
            $comments = $wc . " * SQRT(COALESCE(discussions.comment_count, 0) + 1)";
            $participants = $wp . " * SQRT(COALESCE(discussions.participant_count, 0) + 1)";
            $engagement = "({$likes} + {$comments} + {$participants})";
            $score = "({$engagement}) / POWER({$age}, {$g})";
            $query->reorder()->orderByRaw("{$score} {$direction}");
        } elseif ($driver === 'sqlite') {
            $age = "MAX((julianday('now') - julianday(COALESCE(discussions.last_posted_at, discussions.created_at))) * 24, 0) + 2";
            $likes = "SQRT(COALESCE(discussions.like_count, 0) + 1)";
            $comments = $wc . " * SQRT(COALESCE(discussions.comment_count, 0) + 1)";
            $participants = $wp . " * SQRT(COALESCE(discussions.participant_count, 0) + 1)";
            $engagement = "({$likes} + {$comments} + {$participants})";
            $score = "({$engagement}) / POWER(" . $age . ", " . $g . ")";
            $query->reorder()->orderByRaw("{$score} {$direction}");
        }
    }
}
