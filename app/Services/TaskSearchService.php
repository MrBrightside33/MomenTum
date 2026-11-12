<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;

class TaskSearchService
{
    private const int FETCH_CHUNK_SIZE = 250;

    private const int SUGGESTION_LIMIT = 5;

    /**
     * @return list<array{id: string, title: string, project: string}>
     */
    public function suggestions(User $user, string $query): array
    {
        $normalizedQuery = $this->normalize($query);
        $queryWords = $this->words($normalizedQuery);

        if ($queryWords === []) {
            return [];
        }

        $matches = [];

        foreach (Task::query()
            ->visibleTo($user)
            ->with('project:id,name')
            ->orderBy('id')
            ->lazy(self::FETCH_CHUNK_SIZE) as $task) {
            $score = $this->score($task->title, $normalizedQuery, $queryWords);

            if ($score === null) {
                continue;
            }

            $matches[] = [
                'score' => $score,
                'suggestion' => [
                    'id' => (string) $task->getKey(),
                    'title' => $task->title,
                    'project' => $task->project->name,
                ],
            ];

            if (count($matches) > self::SUGGESTION_LIMIT) {
                $this->sortMatches($matches);
                array_pop($matches);
            }
        }

        $this->sortMatches($matches);

        return array_map(
            static fn (array $match): array => $match['suggestion'],
            array_slice($matches, 0, self::SUGGESTION_LIMIT),
        );
    }

    private function score(string $title, string $normalizedQuery, array $queryWords): ?float
    {
        $normalizedTitle = $this->normalize($title);
        $titleWords = $this->words($normalizedTitle);

        if ($titleWords === []) {
            return null;
        }

        $totalSimilarity = 0.0;

        foreach ($queryWords as $queryWord) {
            $bestSimilarity = 0.0;

            foreach ($titleWords as $titleWord) {
                $bestSimilarity = max(
                    $bestSimilarity,
                    $this->wordSimilarity($queryWord, $titleWord),
                );
            }

            if ($bestSimilarity < 0.65) {
                return null;
            }

            $totalSimilarity += $bestSimilarity;
        }

        $score = $totalSimilarity / count($queryWords);

        if ($normalizedTitle === $normalizedQuery) {
            return $score + 0.5;
        }

        if (str_starts_with($normalizedTitle, $normalizedQuery)) {
            return $score + 0.005;
        }

        if (str_contains($normalizedTitle, $normalizedQuery)) {
            return $score + 0.0025;
        }

        return $score;
    }

    private function wordSimilarity(string $queryWord, string $titleWord): float
    {
        if ($queryWord === $titleWord) {
            return 1.0;
        }

        $queryLength = mb_strlen($queryWord);
        $titleLength = mb_strlen($titleWord);

        if ($queryLength >= 2 && $titleLength > $queryLength && str_starts_with($titleWord, $queryWord)) {
            return 0.65 + 0.1 * ($queryLength / $titleLength);
        }

        $longestLength = max($queryLength, $titleLength);
        $maximumDistance = match (true) {
            $longestLength <= 3 => 0,
            $longestLength <= 5 => 1,
            $longestLength <= 9 => 2,
            default => 3,
        };

        if (abs($queryLength - $titleLength) > $maximumDistance) {
            return 0.0;
        }

        $distance = $this->damerauLevenshteinDistance($queryWord, $titleWord);

        if ($distance > $maximumDistance) {
            return 0.0;
        }

        return 1 - ($distance / $longestLength);
    }

    private function damerauLevenshteinDistance(string $first, string $second): int
    {
        $firstCharacters = mb_str_split($first);
        $secondCharacters = mb_str_split($second);
        $firstLength = count($firstCharacters);
        $secondLength = count($secondCharacters);
        $distances = [];

        for ($row = 0; $row <= $firstLength; $row++) {
            $distances[$row] = array_fill(0, $secondLength + 1, 0);
            $distances[$row][0] = $row;
        }

        for ($column = 0; $column <= $secondLength; $column++) {
            $distances[0][$column] = $column;
        }

        for ($row = 1; $row <= $firstLength; $row++) {
            for ($column = 1; $column <= $secondLength; $column++) {
                $substitutionCost = $firstCharacters[$row - 1] === $secondCharacters[$column - 1] ? 0 : 1;

                $distances[$row][$column] = min(
                    $distances[$row - 1][$column] + 1,
                    $distances[$row][$column - 1] + 1,
                    $distances[$row - 1][$column - 1] + $substitutionCost,
                );

                if (
                    $row > 1
                    && $column > 1
                    && $firstCharacters[$row - 1] === $secondCharacters[$column - 2]
                    && $firstCharacters[$row - 2] === $secondCharacters[$column - 1]
                ) {
                    $distances[$row][$column] = min(
                        $distances[$row][$column],
                        $distances[$row - 2][$column - 2] + 1,
                    );
                }
            }
        }

        return $distances[$firstLength][$secondLength];
    }

    /**
     * @return list<string>
     */
    private function words(string $normalizedText): array
    {
        return $normalizedText === '' ? [] : explode(' ', $normalizedText);
    }

    private function normalize(string $text): string
    {
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($text));

        return trim($normalized ?? '');
    }

    /**
     * @param  list<array{score: float, suggestion: array{id: string, title: string, project: string}}>  $matches
     */
    private function sortMatches(array &$matches): void
    {
        usort(
            $matches,
            static fn (array $first, array $second): int => $second['score'] <=> $first['score']
                ?: strcmp($first['suggestion']['title'], $second['suggestion']['title'])
                ?: strcmp($first['suggestion']['id'], $second['suggestion']['id']),
        );
    }
}
