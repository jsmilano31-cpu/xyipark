$files = @('diagnostic.php','integration_test.php','reserve.php','reservation_details.php','profile.php','my_reservations.php','message.php','dashboard.php','admin/users.php','admin/reservations.php','admin/parking_slots.php','admin/messages.php','admin/includes/sidebar.php','admin/dashboard.php')
foreach ($f in $files) {
    if (Test-Path $f) {
        $content = Get-Content $f -Raw
        $matches = [regex]::Matches($content,'<style>([\s\S]*?)</style>')
        if ($matches.Count -gt 0) {
            $css = ''
            foreach ($m in $matches) { $css += $m.Groups[1].Value + "`n" }
            $basename = [System.IO.Path]::GetFileNameWithoutExtension($f) + '.css'
            $cssPath = Join-Path 'styles' $basename
            Set-Content $cssPath $css
            Write-Output "Extracted CSS to $cssPath"
            $linkhref = if ($f -like 'admin/*') { "../styles/$basename" } else { "styles/$basename" }
            $link = "<link rel=\"stylesheet\" href=\"$linkhref\">"
            $new = [regex]::Replace($content,'<style>[\s\S]*?</style>',$link)
            Set-Content $f $new
            Write-Output "Updated $f"
        }
    }
}
