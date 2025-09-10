$ErrorActionPreference = 'Stop'

Write-Host 'Generating diagrams (PNG) from Mermaid...' -ForegroundColor Cyan
mmdc -i use-cases.mmd -o use-cases.png
mmdc -i sequence-pos-checkout.mmd -o sequence-pos-checkout.png
mmdc -i sequence-supplier-reorder.mmd -o sequence-supplier-reorder.png
mmdc -i classes.mmd -o classes.png

Write-Host 'Generating complete class diagram...' -ForegroundColor Cyan
mmdc -i class-diagram-complete.mmd -o class-diagram-complete.png -t default --width 2400 --height 1800 --scale 2 --backgroundColor white

Write-Host 'Assembling Word document with pandoc...' -ForegroundColor Yellow
pandoc diagrams.md -o diagrams.docx

Write-Host 'Done: diagrams.docx generated in docs/diagrams/' -ForegroundColor Green
Write-Host 'Complete class diagram: class-diagram-complete.png' -ForegroundColor Green

