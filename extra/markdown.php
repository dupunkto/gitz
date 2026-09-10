<?php

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

class GitImageRenderer implements NodeRendererInterface {
  public function __construct(private $repo, private $hash, private string $base) {}

  public function render(Node $node, ChildNodeRendererInterface $childRenderer): string {
    $src = $node->getUrl();

    if (!is_url($src)) {
      $path = trim(path_join($this->base, $src), '/');
      try {
        $blob = \core\getBlob($this->repo, $path, $this->hash);
        $mime = \core\detectMimeType($this->repo, $path, $this->hash);
        $src = "data:{$mime};base64," . base64_encode($blob);
      } catch (\Throwable) {}
    }

    $alt = strip_tags($childRenderer->renderNodes($node->children()));
    $attrs = 'src="' . esc_attr($src) . '" alt="' . esc_attr($alt) . '"';

    if (($title = $node->getTitle()) !== null) {
      $attrs .= ' title="' . esc_attr($title) . '"';
    }

    return "<img {$attrs}>";
  }
}

class GitLinkRenderer implements NodeRendererInterface {
  public function __construct(private $repo, private $hash, private string $base) {}

  private function resolvePath(string $path): string|false {
    $resolved = [];

    foreach(explode('/', $path) as $part) {
      if($part == '' || $part == '.') continue;
      if($part == '..') {
        if(!$resolved) return false;
        array_pop($resolved);
      } else {
        $resolved[] = $part;
      }
    }

    return implode('/', $resolved);
  }

  public function render(Node $node, ChildNodeRendererInterface $childRenderer): string {
    $href = $node->getUrl();

    if(!is_url($href)
      && !str_starts_with($href, '/')
      && !str_starts_with($href, '#')
      && !str_starts_with($href, '?')
      && !str_contains($href, '://')) {
      $suffixOffset = strcspn($href, '?#');
      $path = $this->resolvePath(path_join($this->base, substr($href, 0, $suffixOffset)));

      if($path !== false) {
        $type = \core\getType($this->repo, rawurldecode($path), $this->hash);
        $route = in_array($type, ['blob', 'tree']) ? $type : 'tree';
        $repo_path = ltrim(substr($this->repo->getRepositoryPath(), strlen(SCAN_PATH)), '/');
        $repo_url = GITZ_URL . '/~' . $repo_path;
        $href = $repo_url . '/' . $route . '/' . $this->hash . ($path != '' ? '/' . $path : '') . substr($href, $suffixOffset);
      }
    }

    $text = $childRenderer->renderNodes($node->children());
    $attrs = 'href="' . esc_attr($href) . '"';

    if (($title = $node->getTitle()) !== null) {
      $attrs .= ' title="' . esc_attr($title) . '"';
    }

    return '<a ' . $attrs . '>' . $text . '</a>';
  }
}

class Markdown {
  private MarkdownConverter $converter;
  private $repo;
  private $hash;
  private string $base;

  public function __construct($repo, $hash, string $base) {
    $this->repo = $repo;
    $this->hash = $hash;
    $this->base = $base;

    $env = new Environment(['html_input' => 'allow', 'allow_unsafe_links' => true]);
    $env->addExtension(new CommonMarkCoreExtension());
    $env->addExtension(new AutolinkExtension());
    $env->addExtension(new StrikethroughExtension());
    $env->addExtension(new TableExtension());
    $env->addExtension(new TaskListExtension());
    $env->addRenderer(Image::class, new GitImageRenderer($repo, $hash, $base));
    $env->addRenderer(Link::class, new GitLinkRenderer($repo, $hash, $base));

    $this->converter = new MarkdownConverter($env);
  }

  public function text(string $content): string {
    $html = $this->converter->convert($content)->getContent();
    return preg_replace_callback(
      '/(<img\b[^>]*?\bsrc=)(["\'])([^"\']*)\2/i',
      function ($m) {
        $src = html_entity_decode($m[3], ENT_QUOTES);
        if (!is_url($src)) {
          $path = trim(path_join($this->base, $src), '/');
          try {
            $blob = \core\getBlob($this->repo, $path, $this->hash);
            $mime = \core\detectMimeType($this->repo, $path, $this->hash);
            $src = 'data:' . $mime . ';base64,' . base64_encode($blob);
          } catch (\Throwable) {}
        }
        return $m[1] . $m[2] . esc_attr($src) . $m[2];
      },
      $html
    );
  }
}
