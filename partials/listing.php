<header class="epic">
  <div class="container">
    <h1><?= TITLE ?></h1>
  </div>
</header>

<?php $show_all = @$_GET['show'] == 'all' ?>
<?php $show_legacy = @$_GET['show'] == 'legacy' ?>

<!-- Only show commit graph on normal homepage -->
<?php if(!$show_all && !$show_legacy): ?>
  <picture>
    <source srcset="/api/graph?m=light" media="(prefers-color-scheme: light)"/>
    <source srcset="/api/graph?m=dark" media="(prefers-color-scheme: dark)"/>

    <img
      src="/api/graph"
      width="100%"
      class="container commit-graph"
      alt="Loading commit graph... (turns out analyzing 'git log' for 70+ repos is quite expensive)"
    />
  </picture>
<?php endif; ?>

<?php
  $total_repos = 0;
  $total_size = 0;

  $shared_namespaces = ['axcelott', 'ggijs'];
  $namespace_columns = [$shared_namespaces];

  foreach(array_diff(NAMESPACES, $shared_namespaces) as $namespace) {
    $namespace_columns[] = [$namespace];
  }
?>

<!-- I'm not proud of this mess..., but it works :D -->
<main class="container listing">
  <?php if($show_all || $show_legacy): ?>
    <a class="back" href="/">&larr; Back</a>
  <?php endif; ?>

  <?php foreach($namespace_columns as $column_index => $namespaces): ?>
    <?php
      $shared_column = count($namespaces) > 1;
      $max_repos = match(true) {
        $shared_column => 3,
        $column_index >= 3 => MAX_REPOS - 4,
        default => MAX_REPOS,
      };
    ?>

    <div class="listing-column">
      <?php foreach($namespaces as $namespace): ?>
        <?php
          $repositories = \core\listRepositories($git, $namespace, detailed: true);
          if(empty($repositories)) continue;

          $is_legacy = in_array($namespace, LEGACY);
          $is_hidden = !$show_all && $is_legacy != $show_legacy;
        ?>

        <section <?php if($is_hidden) echo 'hidden' ?>>
          <h2><?= $namespace ?></h2>

          <ul>
            <?php foreach($repositories as $index => $details): ?>
              <?php
                $path = path_join(SCAN_PATH, $namespace, $details['name']);
                $repo = $git->open($path);

                $total_repos++;
                $total_size += \core\getTotalSize($repo);
                $description = \core\getDescription($repo);
              ?>

              <?php if($index == $max_repos): ?>
                </ul>
                <details>
                  <summary>More</summary>
                  <ul>
              <?php endif; ?>

              <li>
                <a href="/~<?= $namespace ?>/<?= $details['name'] ?>">
                  <?php if($details['recent']): ?>
                    <time class="dt" datetime="<?= \dates\isoFormat($details['updated']) ?>"><?= \dates\timeAgo($details['updated']) ?></time>
                  <?php endif; ?>
                  <h3><?= $details['name'] ?></h3>
                  <p><?= $description ?></p>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>

          <?php if(count($repositories) > $max_repos): ?>
            </details>
          <?php endif; ?>
        </section>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

  <?php if(!$show_all && !$show_legacy): ?>
    <a class="legacy" href="/?show=legacy">Show legacy repositories &rarr;</a>
  <?php endif; ?>
</main>

<footer class="container">
  <span><?= $total_repos ?> repos, totalling <?= \core\formatSize($total_size) ?></span>

  <span>
    Powered by <a href="//git.dupunkto.org/dupunkto/gitz">Gitz</a>, 
    a <a href="//dupunkto.org">{du}punkto</a> project.
  </span>
</footer>