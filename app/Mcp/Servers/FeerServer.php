<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateNewsDraftTool;
use App\Mcp\Tools\CreatePageDraftTool;
use App\Mcp\Tools\CreateProjectDraftTool;
use App\Mcp\Tools\GetNewsTool;
use App\Mcp\Tools\GetPageTool;
use App\Mcp\Tools\GetProjectTool;
use App\Mcp\Tools\ListNewsTool;
use App\Mcp\Tools\SearchPagesTool;
use App\Mcp\Tools\SearchProjectsTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('FEER CMS')]
#[Version('0.1.0')]
#[Instructions('Odczyt opublikowanych treści serwisu Fundacji FEER (feer.org.pl): strony, działania i aktualności. Odczyt zwraca wyłącznie treści publiczne. Jedyny zapis to szkic aktualności, działania lub strony (nieopublikowany, wymaga zakresu mcp:write).')]
class FeerServer extends Server
{
    protected array $tools = [
        SearchPagesTool::class,
        GetPageTool::class,
        ListNewsTool::class,
        GetNewsTool::class,
        SearchProjectsTool::class,
        GetProjectTool::class,
        CreateNewsDraftTool::class,
        CreateProjectDraftTool::class,
        CreatePageDraftTool::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
