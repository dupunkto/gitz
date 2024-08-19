<aside class="container summary">
  <section class="logs">
    <h2>Logs</h2>

    <?php $commitId = $repo->getLastCommitId() ?>
    
    <ul>
      <?php for($i = 0; $i < 5; $i++): ?>
        <?php $commit = $repo->getCommit($commitId) ?>
        <?php $commitId = \core\getParent($repo, $commit) ?>
    
        <li>
          <time class="dt"><?= \core\getRelativeDate($commit) ?></time>
          <a class="rev" href="#"><code><?= \core\getShortHash($commit) ?></code></a>
          <span class="message"><?= $commit->getSubject() ?></span>
        </li>

        <?php if(!$commitId) break ?>
      <?php endfor; ?>
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
    <h2>Clone</h2>

    <h3>Read-only</h3>
    <p><a href="//git.dupunkto.org/~<?= $namespace ?>/<?= $repo_name ?>">
      https://git.dupunkto.org/~<?= $namespace ?>/<?= $repo_name ?>
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