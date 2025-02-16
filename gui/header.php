<header>
  <div class="container">
    <h1><a href="/">~<?= $namespace ?></a>/<?= $repo_name ?></h1>
    <?php $branch ??= \core\getDefaultBranch($repo) ?>
    <nav>
      <a href="<?= $repo_url ?>" <?php if($page == "summary") echo 'class="selected"' ?>>Summary</a>
      <a href="<?= $repo_url ?>/log/<?= $branch ?>" <?php if($page == "log") echo 'class="selected"' ?>>Log</a>
      <a href="<?= $repo_url ?>/tree/<?= \core\getLatestCommits($repo, $branch, 1)[0]['hash'] ?>" <?php if(in_array($page, ['tree', 'blob'])) echo 'class="selected"' ?>>Tree</a>
      <?php foreach(\core\listRemotes($repo) as $remote): ?>
        <a href="<?= \core\getRemoteURL($repo, $remote) ?>"><?= $remote ?></a>
      <?php endforeach; ?>
      <?php if(has_tld($repo_name)): ?>
        <a href="//<?= $repo_name ?>">Homepage</a>
      <?php endif; ?>
    </nav>
  </div>
  <div class="line">
    <p class="container description">
      <?php if(in_array($page, ['tree', 'blob'])): ?>
        <code class="mode">
          <?= match($page) {
            'tree' => 'd---------',
            'blob' => '-rw-r--r--'
          } ?>
        </code>
        <code class="path">/<?= $request_path ?></code>
      <?php else: ?>
        <?= \core\getDescription($repo) ?>
      <?php endif; ?>
    </p>
  </div>
</header>