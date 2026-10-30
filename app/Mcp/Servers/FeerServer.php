<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetNewsTool;
use App\Mcp\Tools\GetPageTool;
use App\Mcp\Tools\ListNewsTool;
use App\Mcp\Tools\SearchPagesTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('FEER CMS')]
#[Version('0.1.0')]
#[Instructions('Odczyt opublikowanych treści serwisu Fundacji FEER (feer.org.pl): strony i aktualności. Narzędzia są tylko do odczytu i zwracają wyłącznie treści publiczne.')]
class FeerServer extends Server
{
    protected array $tools = [
        SearchPagesTool::class,
        GetPageTool::class,
        ListNewsTool::class,
        GetNewsTool::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
