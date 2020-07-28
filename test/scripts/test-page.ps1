
param (
[Parameter()][string]$path_title,
[Parameter()][string]$node_title 
)


$dir = "C:\dev\GoldenPaths\main\golden-paths"
$newDir = (Split-Path -Path $dir -Leaf)

robocopy $dir "c:\xampp\htdocs\wp-content\Plugins\$newDir" /MIR /XO  /NFL /NDL /NJH /XD 



cd C:\dev\GoldenPaths\test\codeception\tests\_support\Helper\ 
$out = "";

$exec = "php -r ""require 'debug_tools.php';create_session_on_this_node('$path_title', '$node_title') ;""";
Write-Host (Invoke-Expression $exec -OutVariable out | Tee-Object -Variable out)

cd $dir

$startNpm = {
"npm run build"
}
Start-Job -Name "npm" -ScriptBlock $startNpm

$args = 'http://localhost/kitchen"?"' + $out;

Start-Process -FilePath "c:\program files\mozilla firefox\firefox.exe" -Args $args

Stop-Job -Name "npm"

Pop-Location


