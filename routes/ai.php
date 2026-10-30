<?php

use App\Mcp\Servers\FeerServer;
use Laravel\Mcp\Facades\Mcp;

// Zdalny serwer MCP — tylko dla administratorów z tokenem Sanctum (nagłówek Authorization: Bearer).
Mcp::web('/mcp/feer', FeerServer::class)
    ->middleware(['auth:sanctum', 'throttle:60,1', \App\Http\Middleware\EnsureMcpAdmin::class]);
