$ErrorActionPreference = 'Stop'

Write-Host 'Generating diagrams (PNG) from Mermaid...'
mmdc -i use-cases.mmd -o use-cases.png
mmdc -i sequence-pos-checkout.mmd -o sequence-pos-checkout.png
mmdc -i sequence-supplier-reorder.mmd -o sequence-supplier-reorder.png
mmdc -i classes.mmd -o classes.png

Write-Host 'Assembling Word document with pandoc...'
pandoc diagrams.md -o diagrams.docx

Write-Host 'Done: diagrams.docx generated in docs/diagrams/' -ForegroundColor Green

