<div class="container commit-details">
  <?php $commit = \core\getCommit($repo, $hash) ?>

  <hgroup>
    <h2><?= htmlspecialchars($commit['subject']) ?></h2>

    <p>
      Commited on <time><?= \dates\humanReadable($commit['datetime']) ?></time>
      by <a href="mailto:<?= $commit['email'] ?>"><?= htmlspecialchars($commit['author']) ?></a>.
    </p>
  </hgroup>

  <?php
    $object = $repo->getCommit($hash);
    $message = $object->getBody();

    if($message) echo "<p>" . htmlspecialchars($message) . "</p>";
  ?>

  <pre><code><?= htmlspecialchars($commit['diff']) ?></code></pre>
</div>