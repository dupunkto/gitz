<header class="epic">
  <div class="container">
    <h1>{du}punkto git hosting</h1>
  </div>
</header>

<main class="container listing">
    <?php foreach(NAMESPACES as $namespace): ?>
      <section>
        <h2><?= $namespace ?></h2>
        
        <ul>
          <?php foreach(\core\listRepositories($namespace) as $repo): ?>
            <li><a href="/<?= $namespace ?>/<?= $repo ?>"><?= $repo ?></a></li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endforeach; ?>
</main>