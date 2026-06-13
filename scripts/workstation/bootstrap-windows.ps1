# Ejecutar en PowerShell como usuario normal, no Administrator salvo que winget lo pida.

winget install --id Microsoft.VisualStudioCode -e
winget install --id Git.Git -e
winget install --id GitHub.cli -e
winget install --id Tailscale.Tailscale -e
winget install --id OpenJS.NodeJS.LTS -e
winget install --id PHP.PHP -e
winget install --id Composer.Composer -e

# Extensiones VS Code recomendadas
code --install-extension ms-vscode-remote.remote-ssh
code --install-extension github.vscode-github-actions
code --install-extension github.vscode-pull-request-github
code --install-extension bmewburn.vscode-intelephense-client
code --install-extension redhat.vscode-yaml
code --install-extension ms-playwright.playwright

# Validación
$tools = @("git", "gh", "ssh", "node", "npm", "php", "composer", "tailscale")
foreach ($tool in $tools) {
  Write-Host "Checking $tool..."
  & $tool --version
}
