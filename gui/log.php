<div class="container commit-log">
  <ul>
    <?php foreach(\core\getAllCommits($repo, $branch) as $commit): ?>    
      <li>
        <a href="#">
          <time class="dt"><?= \dates\timeAgo($commit['datetime']) ?></time>
          <code class="rev"><?= \core\toShortHash($commit['hash']) ?></code>
          <span class="message"><?= htmlspecialchars($commit['subject']) ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>