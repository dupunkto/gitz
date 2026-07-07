<div class="container commit-log">
  <ul>
    <?php foreach(\core\getAllCommits($repo, $branch) as $commit): ?>    
      <li>
        <a href="<?= $repo_url ?>/commit/<?= $commit['hash'] ?>">
          <time class="dt" datetime="<?= \dates\isoFormat($commit['datetime']) ?>"><?= \dates\timeAgo($commit['datetime']) ?></time>
          <code class="rev"><?= \core\toShortHash($commit['hash']) ?></code>
          <span class="message"><?= htmlspecialchars($commit['subject']) ?></span>
          <span class="author"><?= htmlspecialchars($commit['author']) ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>