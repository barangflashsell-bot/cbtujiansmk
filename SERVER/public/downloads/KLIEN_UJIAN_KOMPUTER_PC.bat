@echo off
setlocal enabledelayedexpansion
title CBT KLIEN PESERTA LAB - AUTO DETECT SERVER
cls
color 0B

echo ===============================================================================
echo      CBT KLIEN PESERTA LAB - SMK PESANTREN BUSTANUL ULUM
echo ===============================================================================
echo  Peluncur Otomatis Komputer Klien Lab (Mendeteksi Server CBT Mandiri)
echo ===============================================================================
echo.
echo Sedang mendeteksi alamat IP server CBT di jaringan lokal lab...
echo Harap tunggu sebentar...
echo.

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$port = 8000; $foundUrl = $null; " ^
    "$cacheFile = \"$env:TEMP\cbt_server_ip.txt\"; " ^
    "if (Test-Path $cacheFile) { " ^
    "    $cachedIp = (Get-Content $cacheFile -ErrorAction SilentlyContinue).Trim(); " ^
    "    if ($cachedIp) { " ^
    "        try { " ^
    "            $resp = Invoke-WebRequest -Uri \"http://${cachedIp}:${port}/login\" -TimeoutSec 1 -UseBasicParsing -ErrorAction Stop; " ^
    "            if ($resp.StatusCode -eq 200) { $foundUrl = \"http://${cachedIp}:${port}\" } " ^
    "        } catch {} " ^
    "    } " ^
    "}; " ^
    "if (-not $foundUrl) { " ^
    "    $localIps = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue | Where-Object { $_.InterfaceAlias -notmatch 'Loopback|vEthernet' -and $_.IPAddress -notmatch '^169\.254\.' }; " ^
    "    foreach ($ipObj in $localIps) { " ^
    "        $myIp = $ipObj.IPAddress; " ^
    "        try { " ^
    "            $resp = Invoke-WebRequest -Uri \"http://${myIp}:${port}/login\" -TimeoutSec 1 -UseBasicParsing -ErrorAction Stop; " ^
    "            if ($resp.StatusCode -eq 200) { $foundUrl = \"http://${myIp}:${port}\"; break } " ^
    "        } catch {}; " ^
    "        $prefix = $myIp.Substring(0, $myIp.LastIndexOf('.')); " ^
    "        $commonHosts = @(1, 2, 10, 11, 15, 20, 50, 100, 200, 254); " ^
    "        foreach ($h in $commonHosts) { " ^
    "            $target = \"$prefix.$h\"; " ^
    "            try { " ^
    "                $resp = Invoke-WebRequest -Uri \"http://${target}:${port}/login\" -TimeoutSec 1 -UseBasicParsing -ErrorAction Stop; " ^
    "                if ($resp.StatusCode -eq 200 -and ($resp.Content -match 'CBT|Pesantren')) { $foundUrl = \"http://${target}:${port}\"; break } " ^
    "            } catch {} " ^
    "        }; " ^
    "        if ($foundUrl) { break } " ^
    "    } " ^
    "}; " ^
    "if (-not $foundUrl) { " ^
    "    try { " ^
    "        $resp = Invoke-WebRequest -Uri 'https://cbtsmkpesantrenbustanululum.vercel.app/login' -TimeoutSec 3 -UseBasicParsing -ErrorAction Stop; " ^
    "        if ($resp.StatusCode -eq 200) { $foundUrl = 'https://cbtsmkpesantrenbustanululum.vercel.app' } " ^
    "    } catch {} " ^
    "}; " ^
    "if ($foundUrl) { " ^
    "    Set-Content -Path $cacheFile -Value (([System.Uri]$foundUrl).Host) -Force; " ^
    "    Write-Host \"[BERHASIL] Terhubung ke Server CBT: $foundUrl\" -ForegroundColor Green; " ^
    "    $studentUrl = \"$foundUrl/login\"; " ^
    "    $chrome = @(\"$env:ProgramFiles\Google\Chrome\Application\chrome.exe\", \"${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe\"); " ^
    "    $edge = @(\"${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe\", \"$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe\"); " ^
    "    $browserFound = $false; " ^
    "    foreach ($c in $chrome) { if (Test-Path $c) { Start-Process $c -ArgumentList \"--kiosk\", \"--incognito\", \"--disable-pinch\", \"$studentUrl\"; $browserFound = $true; break } }; " ^
    "    if (-not $browserFound) { " ^
    "        foreach ($e in $edge) { if (Test-Path $e) { Start-Process $e -ArgumentList \"--kiosk\", \"--inprivate\", \"$studentUrl\"; $browserFound = $true; break } } " ^
    "    }; " ^
    "    if (-not $browserFound) { Start-Process $studentUrl } " ^
    "} else { " ^
    "    Write-Host \"[GAGAL] Server CBT tidak ditemukan di jaringan lokal maupun cloud.\" -ForegroundColor Red; " ^
    "    Write-Host \"Pastikan kabel LAN atau Wi-Fi terhubung ke jaringan ujian.\" -ForegroundColor Yellow; " ^
    "    Read-Host 'Tekan Enter untuk menutup jendela...' " ^
    "}"

exit /b 0
