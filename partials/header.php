<header>
  <div class="container">
    <h1><a href="/">~<?= $namespace ?></a>/<?= $repo_name ?></h1>
    <?php $branch ??= \core\getDefaultBranch($repo) ?>
    <nav>
      <a href="<?= $repo_url ?>" <?php if($page == "summary") echo 'class="selected"' ?>>Summary</a>
      <a href="<?= $repo_url ?>/log/<?= $branch ?>" <?php if($page == "log") echo 'class="selected"' ?>>Log</a>
      <a href="<?= $repo_url ?>/tree/<?= $branch ?>" <?php if(in_array($page, ['tree', 'blob'])) echo 'class="selected"' ?>>Tree</a>
      <?php foreach(\core\listRemotes($repo) as $remote): ?>
        <a href="<?= \core\getRemoteURL($repo, $remote) ?>"><?= $remote ?></a>
      <?php endforeach; ?>
      <?php if($homepage = \core\getHomepageURL($repo, $repo_name)): ?>
        <a href="<?= $homepage ?>">Homepage</a>
      <?php endif; ?>
      <?php if($docs = \core\getDocumentationURL($repo)): ?>
        <a href="<?= $docs ?>">Documentation</a>
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
        <?php if ($page == 'blob'): ?>
          <a class="download" href="<?= $repo_url ?>/raw/<?= $params[2] ?>/<?= $request_path ?>">view raw</a>
        <?php endif; ?>
      <?php else: ?>
        <?= \core\getDescription($repo) ?>
      <?php endif; ?>
    </p>
  </div>
</header>