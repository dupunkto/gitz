<header>
  <div class="container">
    <h1><a href="/">~<?= $namespace ?></a>/<?= $repo_name ?></h1>
    <?php $branch ??= \core\getDefaultBranch($repo) ?>
    <nav>
      <a class="item-summary <?php if($page == "summary") echo 'selected' ?>" href="<?= $repo_url ?>">Summary</a>
      <a class="item-log <?php if($page == "log") echo 'selected' ?>" href="<?= $repo_url ?>/log/<?= esc_attr($branch) ?>">Log</a>
      <a class="item-tree <?php if(in_array($page, ['tree', 'blob'])) echo 'selected' ?>" href="<?= $repo_url ?>/tree/<?= esc_attr($ref ?? $branch) ?>">Tree</a>
      <?php if($homepage = \core\getHomepageURL($repo, $repo_name)): ?>
        <a  class="item-homepage" href="<?= esc_attr($homepage) ?>">Homepage</a>
      <?php endif; ?>
      <?php if($docs = \core\getDocumentationURL($repo)): ?>
        <a class="item-docs" href="<?= esc_attr($docs) ?>">Docs</a>
      <?php endif; ?>
      <?php if($package = \core\getPackageURL($repo)): ?>
        <a class="item-package" href="<?= esc_attr($package) ?>">Package</a>
      <?php endif; ?>
      <?php foreach(\core\listRemotes($repo) as $remote): ?>
        <a class="item-remote" href="<?= esc_attr(\core\getRemoteURL($repo, $remote)) ?>"><?= esc_inner($remote) ?></a>
      <?php endforeach; ?>
      <?php if($login = \core\getLoginURL($repo)): ?>
        <a class="item-login" href="<?= esc_attr($login) ?>">Login &rarr;</a>
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
        <code class="path">/<?= esc_inner($request_path) ?></code>
      <?php endif; ?>

      <?php if(isset($hash) && isset($ref)): ?>
        <?php if($ref == $hash): ?>
          <code class="rev">rev:
            <a href="<?= esc_attr(path_join($repo_url, "commit", $hash)) ?>">
              <?= esc_inner(substr($hash, 0, 7)) ?>
            </a>
          </code>
          <a class="permalink" href="<?= esc_attr(path_join($repo_url, $page, $branch, $request_path)) ?>">
            view latest
          </a>
        <?php else: ?>
          <?php $branches = $repo->getLocalBranches() ?>
          <?php if(count($branches) > 1): ?>
            <code class="branch">
              branch:
              <select onchange='window.location = `<?= esc_attr(path_join($repo_url, $page)) ?>` + `/${event.target.value}/` + `<?= esc_attr($request_path) ?>`'>
                <?php foreach($branches as $branch): ?>
                  <option <?php if($params[2] == $branch) echo "selected" ?>>
                    <?= esc_inner($branch) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </code>
          <?php endif; ?>
          <a class="permalink" href="<?= esc_attr(path_join($repo_url, $page, $hash, $request_path)) ?>">
            permalink
          </a>
        <?php endif; ?>
      <?php else: ?>
        <?= \core\getDescription($repo) ?>
      <?php endif; ?>

      <?php if ($page == 'blob'): ?>
        <a class="download" href="<?= $repo_url ?>/raw/<?= esc_attr($params[2]) ?>/<?= esc_attr($request_path) ?>">view raw</a>
      <?php endif; ?>
    </p>
  </div>
</header>