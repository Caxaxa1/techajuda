<#
Interactive helper to create/update %USERPROFILE%\.aws\credentials and optionally %USERPROFILE%\.aws\config
This script does NOT transmit credentials anywhere. It writes to the local user's profile folder.
Usage examples:
  .\create-aws-credentials.ps1 -ProfileName default -SetRegion
  .\create-aws-credentials.ps1
#>
param(
    [string]$ProfileName = 'default',
    [switch]$SetRegion,
    [string]$AccessKey,
    [string]$SecretKey,
    [string]$Region
)

function Read-Secret($prompt) {
    $secure = Read-Host $prompt -AsSecureString
    return [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure))
}

try {
    $awsDir = Join-Path $env:USERPROFILE '.aws'
    if (-not (Test-Path $awsDir)) {
        New-Item -ItemType Directory -Path $awsDir | Out-Null
    }

    if (-not $ProfileName) { $ProfileName = 'default' }

    if (-not $AccessKey) {
        $accessKey = Read-Host "AWS Access Key ID for profile '$ProfileName'"
    } else { $accessKey = $AccessKey }

    if (-not $SecretKey) {
        $secretKey = Read-Secret "AWS Secret Access Key for profile '$ProfileName'"
    } else { $secretKey = $SecretKey }

    $credentialsPath = Join-Path $awsDir 'credentials'
    # build the entry using format to avoid accidental expansion issues
    $entry = "[{0}]`naws_access_key_id = {1}`naws_secret_access_key = {2}`n" -f $ProfileName, $accessKey, $secretKey

    if (Test-Path $credentialsPath) {
        $content = Get-Content $credentialsPath -Raw
        # regex: match profile block starting at line beginning until next [header] or EOF
        $pattern = '^\[' + [regex]::Escape($ProfileName) + '\].*?(?=^\[|$)'
        $regexOptions = [System.Text.RegularExpressions.RegexOptions]::Singleline -bor [System.Text.RegularExpressions.RegexOptions]::Multiline
        if ([System.Text.RegularExpressions.Regex]::IsMatch($content, $pattern, $regexOptions)) {
            $newContent = [System.Text.RegularExpressions.Regex]::Replace($content, $pattern, $entry, $regexOptions)
            Set-Content -Path $credentialsPath -Value $newContent -Force -Encoding Ascii
            Write-Host "Profile '$ProfileName' atualizado em $credentialsPath"
        } else {
            Add-Content -Path $credentialsPath -Value "`n$entry" -Encoding Ascii
            Write-Host "Profile '$ProfileName' adicionado em $credentialsPath"
        }
    } else {
        Set-Content -Path $credentialsPath -Value $entry -Encoding Ascii
        Write-Host "Arquivo $credentialsPath criado com profile '$ProfileName'"
    }

    if ($SetRegion -or $Region) {
        if ($Region) { $region = $Region } else { $region = Read-Host "Região padrão (ex: us-east-1)" }
        $configPath = Join-Path $awsDir 'config'
        $configEntry = "[profile $ProfileName]`nregion = $region`n"
        if (Test-Path $configPath) {
            $c = Get-Content $configPath -Raw
            $p = '^\[profile\s+' + [regex]::Escape($ProfileName) + '\].*?(?=^\[profile|$)'
            if ([System.Text.RegularExpressions.Regex]::IsMatch($c, $p, $regexOptions)) {
                $newc = [System.Text.RegularExpressions.Regex]::Replace($c, $p, $configEntry, $regexOptions)
                Set-Content -Path $configPath -Value $newc -Force -Encoding Ascii
                Write-Host "Config profile '$ProfileName' atualizado em $configPath"
            } else {
                Add-Content -Path $configPath -Value "`n$configEntry" -Encoding Ascii
                Write-Host "Config profile '$ProfileName' adicionado em $configPath"
            }
        } else {
            Set-Content -Path $configPath -Value $configEntry -Encoding Ascii
            Write-Host "Arquivo $configPath criado com profile '$ProfileName'"
        }
    }

    # clear secret from memory
    $secretKey = $null
    [GC]::Collect()

    Write-Host "Pronto. Arquivo: $credentialsPath"
    Write-Host "Para usar este profile na sessão PowerShell atual execute: `"`$env:AWS_PROFILE = '$ProfileName'`""
}
catch {
    Write-Error "Erro ao criar/atualizar credenciais: $_"
}