<main class="container summary">
  <section class="logs">
    <h2>Logs <small><?= \core\getTotalCommits($repo) ?> commits</small></h2>
    
    <ul>
      <?php foreach(\core\getLatestCommits($repo) as $commit): ?>    
        <li>
          <time class="dt" datetime="<?= \dates\isoFormat($commit['datetime']) ?>"><?= \dates\timeAgo($commit['datetime']) ?></time>
          <a class="rev" href="<?= $repo_url ?>/commit/<?= $commit['hash'] ?>"><code
            ><?= \core\toShortHash($commit['hash']) ?></code
          ></a>
          <span class="message"><?= htmlspecialchars($commit['subject']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <section class="branches">
    <h2>Branches</h2>
    
    <?php foreach($repo->getLocalBranches() as $branch): ?>
      <h3 <?php if(\core\isHEAD($repo, $branch)) echo 'class="head"' ?>>
        <?= esc_inner($branch) ?>
      </h3>
      <p>
        <a href="<?= $repo_url ?>/tree/<?= esc_attr($branch) ?>">tree</a>
        <a href="<?= $repo_url ?>/log/<?= esc_attr($branch) ?>">log</a>
      </p>
    <?php endforeach; ?>
  </section>
  <section class="clone">
    <h2>Clone <small><?= \core\formatTotalSize($repo) ?></small></h2>

    <h3>Read-only</h3>
    <p><a href="<?= HTTP_BASE ?>/~<?= $namespace ?>/<?= $repo_name ?>">
      <?= HTTP_BASE ?>/~<?= $namespace ?>/<?= $repo_name ?>.git
    </a></p>

    <h3>Read/write</h3>
    <p><?= USER ?>@<?= SSH_BASE ?>:<?= $namespace ?>/<?= $repo_name ?></p>

    <small>You can contribute changes using <a href="//git-send-email.io">git send-email</a>.</small>
  </section>

  <?php $contributors = \core\getContributors($repo) ?>
  <?php $total = array_sum(array_map(fn($c) => $c['count'], $contributors)) ?>
  <?php $max_count = max(array_map(fn($c) => $c['count'], $contributors)) ?>

  <aside class="sidebar">
    <section class="contributors">
      <?php $n = count($contributors) ?>
      <h2>Contributions <small><?= $n ?> <?= $n === 1 ? 'contributor' : 'contributors' ?></small></h2>

      <div class="contributor-bar">
        <?php foreach($contributors as $contributor): ?>
          <?php
            $ratio = $contributor['count'] / $max_count;
            $light = lighten('#7426e2', 0.75 * (1 - $ratio));
            $dark  = lighten('#7426e2', 0.4 * $ratio);
          ?>
          <span
            style="width: <?= round($contributor['count'] / $total * 100, 3) ?>%; background: light-dark(<?= $light ?>, <?= $dark ?>)"
            title="<?= esc_attr($contributor['author']) ?>: <?= $contributor['count'] ?>/<?= $total ?> commits">
          </span>
        <?php endforeach; ?>
      </div>

      <ul class="contributor-list">
        <?php foreach($contributors as $contributor): ?>
          <?php
            $ratio = $contributor['count'] / $max_count;
            $light = lighten('#7426e2', 0.75 * (1 - $ratio));
            $dark  = lighten('#7426e2', 0.4 * $ratio);
          ?>
          <li>
            <a href="mailto:<?= esc_attr($contributor['email']) ?>" class="contributor-item" title="<?= esc_attr($contributor['count']) ?> commits">
              <span class="contributor-dot" style="background: light-dark(<?= $light ?>, <?= $dark ?>)"></span><?= esc_inner($contributor['author']) ?> &lt;<?= esc_inner($contributor['email']) ?>&gt;
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <?php if($remotes = \core\listRemotes($repo)): ?>
      <section class="mirrors">
        <h2>Mirrors</h2>

        <?php foreach($remotes as $remote): ?>
          <p>
            <b><?= esc_inner($remote) ?></b><br>
            <a href="<?= esc_attr(\core\getRemoteURL($repo, $remote)) ?>"><?= esc_inner(\core\getRemoteURL($repo, $remote)) ?></a></p>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </aside>

  <article class="readme">
    <?php
      $blob = \core\getREADME($repo);
      $hash = \core\getLatestHash($repo, $branch);

      $parser = new Markdown($repo, $hash, '/');

      echo $parser->text($blob);
    ?>
  </article>
</main>
