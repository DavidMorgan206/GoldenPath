
param (
[Parameter()][boolean]$cleanEnv=$false,
[Parameter()][boolean]$cleanGpaths=$false, 
[Parameter()][boolean]$skipTests=$false 
)

$dir = "C:\dev\GoldenPaths\main\golden-paths"
$newDir = (Split-Path -Path $dir -Leaf)

robocopy $dir "c:\xampp\htdocs\wp-content\Plugins\$newDir" /MIR /XO  /NFL /NDL /NJH /XD 

if($skipTests -eq $false)
{
    #java -jar "C:\tools\selenium-server-standalone-3.141.59.jar" #Launch Selenium Server before executing tests.
    #TODO: only launch if not running. launch in seperate background window
    
    cd C:\dev\GoldenPaths\test\codeception

    #c:\dev\goldenpaths\test\codeception\vendor\bin\codecept.bat run functional AdminViewCest:NodeCrudView
    c:\dev\goldenpaths\test\codeception\vendor\bin\codecept.bat  run 

    

    #c:\dev\goldenpaths\vendor\bin\codecept.bat --debug run functional
    #c:\dev\goldenpaths\test\codeception\vendor\bin\codecept.bat run functional ModelCest:createAndDeleteNode
    #c:\dev\goldenpaths\vendor\bin\codecept.bat run acceptance UserFlowPageCest:BasicFlow
    #c:\dev\goldenpaths\vendor\bin\codecept.bat run acceptance UserFlowPageCest:TestPathLandingCest

    
}

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

#$response = read-host

#$debuglog = "C:\xampp\apache\logs\error.log";
#if((Test-Path -Path $debuglog))
#{
#    write-host -fore Red "`nApache Error Log non-empty, here it is:`r`n===========================================`r`n"
#    write-host -fore Red (Get-Content -Path $debuglog);
#}
