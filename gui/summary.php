<aside class="container summary">
  <section class="logs">
    <h2>Logs <small><?= \core\getTotalCommits($repo) ?> commits</small></h2>
    
    <ul>
      <?php foreach(\core\getLatestCommits($repo) as $commit): ?>    
        <li>
          <time class="dt"><?= \dates\timeAgo($commit['datetime']) ?></time>
          <a class="rev" href="#"><code><?= \core\toShortHash($commit['hash']) ?></code></a>
          <span class="message"><?= $commit['subject'] ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <section class="branches">
    <h2>Branches</h2>
    
    <?php foreach($repo->getLocalBranches() as $branch): ?>
      <h3 <?php if(\core\isHEAD($repo, $branch)) echo 'class="head"' ?>>
        <?= $branch ?>
      </h3>
      <p><a href="">tree</a> <a href="">log</a></p>
    <?php endforeach; ?>
  </section>
  <section class="clone">
    <h2>Clone <small><?= \core\formatTotalSize($repo) ?></small></h2>

    <h3>Read-only</h3>
    <p><a href="//git.dupunkto.org/~<?= $namespace ?>/<?= $repo_name ?>">
      https://git.dupunkto.org/~<?= $namespace ?>/<?= $repo_name ?>.git
    </a></p>

    <h3>Read/write</h3>
    <p>robinb@dupunkto.org:<?= $namespace ?>/<?= $repo_name ?></p>

    <small>You can contribute changes using <a href="//git-send-email.io">git send-email</a>.</small>
  </section>
</aside>

<main class="container">
  <article class="readme">
    <?php
      $markdown = \core\getREADME($repo);
      $parser = new Parsedown();

      echo $parser->text($markdown);
    ?>
  </article>
</main>