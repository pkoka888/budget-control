# Master script to run commit loops for all git repositories
# This script will run the commit and test loops for all found git repositories

param(
    [int]$LoopCount = 3,  # Number of loops to run for each repo
    [int]$DelaySeconds = 15  # Delay between loops
)

Write-Host "🚀 Running Commit Loops for All Git Repositories" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "Loop Count per repo: $LoopCount" -ForegroundColor Yellow
Write-Host "Delay: $DelaySeconds seconds" -ForegroundColor Yellow
Write-Host ""

# Define repositories to check
$repos = @(
    @{ Name = "Maybe Finance"; Path = "maybe"; Script = "GIT-COMMIT-LOOP.ps1" },
    @{ Name = "Firefly III"; Path = "firefly-iii"; Script = "GIT-COMMIT-LOOP.ps1" }
)

foreach ($repo in $repos) {
    Write-Host "📁 Processing repository: $($repo.Name)" -ForegroundColor Magenta
    Write-Host "======================================" -ForegroundColor Magenta

    $scriptPath = Join-Path $repo.Path $repo.Script

    if (Test-Path $scriptPath) {
        Write-Host "✅ Found commit script: $scriptPath" -ForegroundColor Green

        # Run the commit loop
        try {
            Write-Host "🔄 Running commit loop for $($repo.Name)..." -ForegroundColor Blue
            Push-Location $repo.Path
            & powershell -ExecutionPolicy Bypass -File $repo.Script -LoopCount $LoopCount -DelaySeconds $DelaySeconds
            Pop-Location
        } catch {
            Write-Host "❌ Failed to run commit loop for $($repo.Name): $($_.Exception.Message)" -ForegroundColor Red
            # Make sure we return to original location even on error
            try { Pop-Location } catch { }
        }
    } else {
        Write-Host "⚠️  Commit script not found: $scriptPath" -ForegroundColor Yellow
    }

    Write-Host ""
}

Write-Host "🎉 All repository commit loops completed!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host "Summary:" -ForegroundColor Cyan
Write-Host "- Processed repositories: $($repos.Count)" -ForegroundColor White

# Show final status for each repo
foreach ($repo in $repos) {
    Write-Host ""
    Write-Host "$($repo.Name) final status:" -ForegroundColor Yellow
    try {
        Push-Location $repo.Path
        git status --short
        Write-Host "Recent commits:" -ForegroundColor Blue
        git log --oneline -2
    } catch {
        Write-Host "❌ Could not get status for $($repo.Name)" -ForegroundColor Red
    } finally {
        Pop-Location
    }
}

Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Review all commits: git log --oneline" -ForegroundColor White
Write-Host "2. Push changes: git push origin main" -ForegroundColor White
Write-Host "3. Check server logs if issues occurred" -ForegroundColor White
Write-Host "4. Run this script again with: .\RUN-ALL-COMMIT-LOOPS.ps1 -LoopCount 5" -ForegroundColor White
