<?php
// Utilities for working with dates.

namespace dates;

use DateTimeImmutable;

function timeAgo(DateTimeImmutable $dateTime) {
  $now = new DateTimeImmutable();
  $diff = $now->diff($dateTime);

  if ($diff->y > 0)  return $dateTime->format('Y-m-d');
  if ($diff->m > 0)  return $diff->m === 1 ? 'last month' : $diff->m . ' months ago';
  if ($diff->d >= 7) return 'last week';
  if ($diff->d > 0)  return $diff->d === 1 ? 'yesterday' : $diff->d . ' days ago';
  if ($diff->h > 0)  return $diff->h === 1 ? 'an hour ago' : $diff->h . ' hours ago';
  if ($diff->i > 0)  return $diff->i === 1 ? 'a minute ago' : $diff->i . ' minutes ago';

  return 'just now';
}
