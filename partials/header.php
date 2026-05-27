<header>
  <div class="container">
    <h1><a href="/">~<?= $namespace ?></a>/<?= $repo_name ?></h1>
    <?php $branch ??= \core\getDefaultBranch($repo) ?>
    <nav>
      <a class="item-summary <?php if($page == "summary") echo 'selected' ?>" href="<?= $repo_url ?>">Summary</a>
      <a class="item-log <?php if($page == "log") echo 'selected' ?>" href="<?= $repo_url ?>/log/<?= $branch ?>">Log</a>
      <a class="item-tree <?php if(in_array($page, ['tree', 'blob'])) echo 'selected' ?>" href="<?= $repo_url ?>/tree/<?= @$params[2] ?? $branch ?>">Tree</a>
      <?php if($homepage = \core\getHomepageURL($repo, $repo_name)): ?>
        <a  class="item-homepage" href="<?= $homepage ?>">Homepage</a>
      <?php endif; ?>
      <?php if($docs = \core\getDocumentationURL($repo)): ?>
        <a class="item-docs" href="<?= $docs ?>">Docs</a>
      <?php endif; ?>
      <?php if($package = \core\getPackageURL($repo)): ?>
        <a class="item-package" href="<?= $package ?>">Package</a>
      <?php endif; ?>
      <?php foreach(\core\listRemotes($repo) as $remote): ?>
        <a class="item-remote" href="<?= \core\getRemoteURL($repo, $remote) ?>"><?= $remote ?></a>
      <?php endforeach; ?>
      <?php if($login = \core\getLoginURL($repo)): ?>
        <a class="item-login" href="<?= $login ?>">Login &rarr;</a>
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
        <?php if(\core\isCommitHash(@$params[2])): ?>
          <code class="rev">rev: <a href="<?= $repo_url ?>/commit/<?= esc_attr($params[2]) ?>"><?= esc_inner(substr($params[2], 0, 7)) ?></a></code>
          <a class="permalink" href="<?= $repo_url ?>/<?= $page ?>/<?= $branch ?>/<?= $request_path ?>">view latest</a>
        <?php else: ?>
          <a class="permalink" href="<?= $repo_url ?>/<?= $page ?>/<?= $hash ?>/<?= $request_path ?>">permalink</a>
        <?php endif; ?>
        <?php if ($page == 'blob'): ?>
          <a class="download" href="<?= $repo_url ?>/raw/<?= esc_attr($params[2]) ?>/<?= $request_path ?>">view raw</a>
        <?php endif; ?>
      <?php else: ?>
        <?= \core\getDescription($repo) ?>
      <?php endif; ?>
    </p>
  </div>
</header>