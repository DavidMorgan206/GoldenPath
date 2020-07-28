
param (
[Parameter()][boolean]$cleanEnv=$false,
[Parameter()][boolean]$cleanGpaths=$false, 
[Parameter()][boolean]$skipTests=$false,
[Parameter()][boolean]$skipNpm=$false 
)

# run-tests.ps1
# TODO abort on failure
# pass path to run-tests


$dir = "C:\dev\GoldenPaths\main\golden-paths"
$newDir = (Split-Path -Path $dir -Leaf)

robocopy $dir "c:\xampp\htdocs\wp-content\Plugins\$newDir" /MIR /XO  /NFL /NDL /NJH /XD 

$debuglog = "C:\xampp\htdocs\wp-content\debug.log";
if((Test-Path -Path $debuglog))

{

    write-host -fore Yellow "`nWordpress Error Log non-empty, here it is:`r`n===========================================`r`n"
    write-host -fore White ((Get-Content -Path $debuglog) -join "`n");
    if(Test-Path ".\debug.log")
    {
        Remove-item ".\debug.log"
    }
    Move-Item $debuglog ".\debug.log"
}

write-host `n`n`n



if(-not $skipNpm)
{
    populate-test-data.ps1    
    cd $dir
    npm run build

}