#$exec = "php include C:\dev\GoldenPaths\tests\_support\Helper\debug_tools.php;populate_test_content(1) -r" 


Push-Location C:\dev\GoldenPaths\test\codeception\tests\_support\Helper\ 
$out = "";

$exec = "php -r ""require 'debug_tools.php';delete_all_table_data();""";
Write-Host (Invoke-Expression $exec -OutVariable out | Tee-Object -Variable out)

$exec = "php -r ""require 'debug_tools.php';populate_test_content();""";
Write-Host (Invoke-Expression $exec -OutVariable out | Tee-Object -Variable out)

Pop-Location
#Write-Host $out;


