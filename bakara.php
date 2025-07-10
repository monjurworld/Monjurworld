<?php

$html = file_get_contents('http://localhost/monju/bakara.html');
if ($html === FALSE) {
    die("Error: Could not read the HTML file.");
}

libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($html);
libxml_clear_errors();

$xPath = new DOMXPath($dom);

// The main target is the 'div.row align-items-center mb-2' which encapsulates each full verse.
$all_verse_blocks = $xPath->query('//div[contains(@class, "row") and contains(@class, "align-items-center") and contains(@class, "mb-2")]');

$extracted_data = []; // Initialize an array to store all extracted rows

foreach ($all_verse_blocks as $verseBlock) {
    // 1. Extract the verse number first from the current verse block
    $verseNoNode = $xPath->query('.//div[contains(@class, "col-12")]/p[contains(@class, "badge") and contains(@class, "rounded-pill")]', $verseBlock)->item(0);
    $currentVerseNumber = 'N/A';
    if ($verseNoNode) {
        $currentVerseNumber = trim($verseNoNode->textContent);
        // Remove parentheses around the verse number, if present
        $currentVerseNumber = trim($currentVerseNumber, '()');
    }

    // 2. Now, find all 'div.p-2' elements *within this specific verse block*
    $all_p2_divs_in_block = $xPath->query('.//div[contains(@class, "p-2")]', $verseBlock);

    foreach ($all_p2_divs_in_block as $p2Div) {
        // Find the span containing the Arabic text.
        //$spanNode = $xPath->query('.//span[@dir="rtl" and @lang="ar" and contains(@class, "h4")]', $p2Div)->item(0);
		 $spanNode = $xPath->query('.//span[starts-with(@id, "text-") and @dir="rtl" and @lang="ar"]', $p2Div)->item(0);

        // Find the div containing the Bengali and English text.
        $meaningDiv = $xPath->query('.//div[contains(@class, "small") and contains(@class, "font-kalpurush-reading") and contains(@class, "text-muted") and contains(@class, "text-center")]', $p2Div)->item(0);

        // Find the audio source URL.
        $audioSourceNode = $xPath->query('.//audio/source', $p2Div)->item(0);
		

      $arabicText = 'N/A';
    $arabicId = ''; // To store the ID for JavaScript playback
    if ($spanNode) {
        $text = $spanNode->textContent;
        $arabicText = str_replace('\xC5\xA0', ' ', $text);
        $arabicText = preg_replace('/\p{C}+/u', '', $arabicText);
        $arabicText = trim($arabicText);
        if ($spanNode->hasAttribute('id')) {
            // Extract the part of the ID that playAudio uses (e.g., '001_001_001')
            $fullId = $spanNode->getAttribute('id');
            // Assuming the ID format is always 'text-YOUR_ID'
            $arabicId = str_replace('text-', '', $fullId);
        }
    }

        $bengaliText = 'N/A';
        $englishText = 'N/A';
        if ($meaningDiv) {
            $meaningContentHtml = $meaningDiv->ownerDocument->saveHTML($meaningDiv);
            $meaningContentHtml = preg_replace('/<br\s*\/?>/i', '<br>', $meaningContentHtml); // Normalize <br> tags
            $parts = explode('<br>', $meaningContentHtml);

            if (count($parts) >= 2) {
                $bengaliText = trim(strip_tags($parts[0]));
                $englishText = trim(strip_tags($parts[1]));
            } else {
                $bengaliText = trim(strip_tags($meaningContentHtml));
                $englishText = 'N/A';
            }
        }

        $audioLink = 'N/A';
        if ($audioSourceNode && $audioSourceNode->hasAttribute('src')) {
            $audioLink = $audioSourceNode->getAttribute('src');
        }

        // Add all extracted data, including the current verse number
        $extracted_data[] = [
            'verse_no' => $currentVerseNumber, // This is the verse number for the *entire block*
            'arabic' => $arabicText,
            'bengali' => $bengaliText,
            'english' => $englishText,
			'audio_id' => $arabicId, // Store the extracted audio ID part
        'audio_src' => $audioLink // Store the direct audio source link
        ];
    }
}

// Now, output the data in an HTML table, including the audio link.
// Add a JavaScript function to play audio when Arabic word is clicked
// This function needs to be defined once in the HTML head or before the table.
echo '<script>
function playAudioByUrl(audioUrl) {
    if (audioUrl && audioUrl !== "N/A") {
        var audio = new Audio(audioUrl);
        audio.play().catch(e => console.error("Error playing audio:", e));
    } else {
        console.warn("Audio URL not available.");
    }
}
</script>';

echo "<table border='1' align='center' cellpadding='5' cellspacing='0'>";
echo "<thead><tr><th>Verse_no</th><th>Arabic</th><th>Bengali</th><th>English</th></tr></thead>";
echo "<tbody>";
foreach ($extracted_data as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['verse_no']) . "</td>";
	
    echo "<td>";
    if ($row['audio_src'] !== 'N/A') {
        // Make the Arabic word clickable to play audio using the direct URL
        echo "<a href='javascript:void(0)' onclick=\"playAudioByUrl('" . htmlspecialchars($row['audio_src'], ENT_QUOTES, 'UTF-8') . "')\">" . htmlspecialchars($row['arabic']) . "</a>";
    } else {
        echo htmlspecialchars($row['arabic']);
    }
    echo "</td>";
    echo "<td>" . htmlspecialchars($row['bengali']) . "</td>";
    echo "<td>" . htmlspecialchars($row['english']) . "</td>";
   
    echo "</tr>";
}
echo "</tbody>";
echo "</table>";

?>