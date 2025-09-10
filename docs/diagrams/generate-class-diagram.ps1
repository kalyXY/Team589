# Script PowerShell pour generer le diagramme de classe UML de Scolaria
# Requires: mermaid-cli (mmdc) installe globalement via npm

param(
    [string]$OutputDir = ".",
    [string]$Format = "png",
    [switch]$OpenAfter = $false
)

# Configuration
$InputFile = "class-diagram-complete.mmd"
$OutputFile = "class-diagram-complete.$Format"
$FullOutputPath = Join-Path $OutputDir $OutputFile

Write-Host "Generation du diagramme de classe UML - Scolaria" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan

# Verifier que le fichier source existe
if (-not (Test-Path $InputFile)) {
    Write-Error "Fichier source non trouve: $InputFile"
    Write-Host "Assurez-vous d'etre dans le repertoire docs/diagrams/" -ForegroundColor Yellow
    exit 1
}

# Verifier que mermaid-cli est installe
try {
    $mmdc = Get-Command mmdc -ErrorAction Stop
    Write-Host "mermaid-cli trouve: $($mmdc.Source)" -ForegroundColor Green
} catch {
    Write-Error "mermaid-cli (mmdc) non trouve!"
    Write-Host "Installation requise:" -ForegroundColor Yellow
    Write-Host "  npm install -g @mermaid-js/mermaid-cli" -ForegroundColor White
    Write-Host "  Ou: npm install -g mermaid" -ForegroundColor White
    exit 1
}

# Generer le diagramme
Write-Host "Generation en cours..." -ForegroundColor Yellow
Write-Host "  Entree: $InputFile"
Write-Host "  Sortie: $FullOutputPath"
Write-Host "  Format: $Format"

try {
    # Options pour mermaid-cli avec syntaxe simplifiee
    $mmdc_args = @(
        "-i", $InputFile,
        "-o", $FullOutputPath,
        "-t", "default",
        "--width", "2400",
        "--height", "1800",
        "--scale", "2",
        "--backgroundColor", "white"
    )
    
    # Ajouter des options specifiques selon le format
    if ($Format -eq "svg") {
        $mmdc_args += @("--svg")
    } elseif ($Format -eq "pdf") {
        $mmdc_args += @("--pdf")
    }
    
    & mmdc @mmdc_args
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Diagramme genere avec succes!" -ForegroundColor Green
        
        # Informations sur le fichier genere
        if (Test-Path $FullOutputPath) {
            $fileInfo = Get-Item $FullOutputPath
            Write-Host "Fichier: $($fileInfo.FullName)" -ForegroundColor Cyan
            Write-Host "Taille: $([math]::Round($fileInfo.Length / 1KB, 2)) KB" -ForegroundColor Cyan
            Write-Host "Cree: $($fileInfo.CreationTime)" -ForegroundColor Cyan
            
            # Ouvrir le fichier si demande
            if ($OpenAfter) {
                Write-Host "Ouverture du fichier..." -ForegroundColor Yellow
                Start-Process $FullOutputPath
            }
        }
    } else {
        Write-Error "Erreur lors de la generation (code: $LASTEXITCODE)"
        exit 1
    }
    
} catch {
    Write-Error "Erreur lors de l'execution: $($_.Exception.Message)"
    exit 1
}

# Generation de formats additionnels
Write-Host ""
Write-Host "Options de generation supplementaires:" -ForegroundColor Magenta
Write-Host "  PowerShell .\generate-class-diagram.ps1 -Format svg     # Format vectoriel"
Write-Host "  PowerShell .\generate-class-diagram.ps1 -Format pdf     # Format PDF"
Write-Host "  PowerShell .\generate-class-diagram.ps1 -OpenAfter      # Ouvrir apres generation"

# Proposer de generer d'autres formats
$response = Read-Host "Voulez-vous generer d'autres formats? (svg/pdf/non) [non]"
switch ($response.ToLower()) {
    "svg" {
        Write-Host "Generation SVG..." -ForegroundColor Yellow
        & mmdc -i $InputFile -o "class-diagram-complete.svg" -t default --svg --backgroundColor white
        if ($LASTEXITCODE -eq 0) {
            Write-Host "SVG genere: class-diagram-complete.svg" -ForegroundColor Green
        }
    }
    "pdf" {
        Write-Host "Generation PDF..." -ForegroundColor Yellow
        & mmdc -i $InputFile -o "class-diagram-complete.pdf" -t default --pdf --backgroundColor white
        if ($LASTEXITCODE -eq 0) {
            Write-Host "PDF genere: class-diagram-complete.pdf" -ForegroundColor Green
        }
    }
    default {
        Write-Host "Generation terminee!" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "Recapitulatif des fichiers generes:" -ForegroundColor Cyan
Get-ChildItem -Path . -Filter "class-diagram-complete.*" | ForEach-Object {
    Write-Host "  $($_.Name) ($([math]::Round($_.Length / 1KB, 2)) KB)" -ForegroundColor White
}

Write-Host ""
Write-Host "Generation terminee avec succes!" -ForegroundColor Green
Write-Host "Consultez class-diagram-documentation.md pour la documentation complete." -ForegroundColor Cyan
