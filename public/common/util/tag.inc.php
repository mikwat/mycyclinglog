<?php
/**
 * Parses a String of Tags
 *
 * Tags are space delimited. Either single or double quotes mark a phrase.
 * Odd quotes will cause everything on their right to reflect as one single
 * tag or phrase. All white-space within a phrase is converted to single
 * space characters. Quotes burried within tags are ignored! Duplicate tags
 * are ignored, even duplicate phrases that are equivalent.
 *
 * Returns an array of tags.
 */
function parse_tag_str($sTagString)
{
  $arTags = array();    // Array of Output
  $cPhraseQuote = null; // Record of the quote that opened the current phrase
  $sPhrase = null;    // Temp storage for the current phrase we are building

  // Define some constants
  static $sTokens = " \r\n\t";  // Space, Return, Newline, Tab
  static $sQuotes = "'\"";    // Single and Double Quotes

  // Start the State Machine
  do
  {
    // Get the next token, which may be the first
    $sToken = isset($sToken)? strtok($sTokens) : strtok($sTagString, $sTokens);

    // Are there more tokens?
    if ($sToken === false)
    {
      // Ensure that the last phrase is marked as ended
      $cPhraseQuote = null;
    }
    else
    {
      // Are we within a phrase or not?
      if ($cPhraseQuote !== null)
      {
        // Will the current token end the phrase?
        if (substr($sToken, -1, 1) === $cPhraseQuote)
        {
          // Trim the last character and add to the current phrase, with a single leading space if necessary
          if (strlen($sToken) > 1) $sPhrase .= ((strlen($sPhrase) > 0)? ' ' : null) . substr($sToken, 0, -1);
          $cPhraseQuote = null;
        }
        else
        {
          // If not, add the token to the phrase, with a single leading space if necessary
          $sPhrase .= ((strlen($sPhrase) > 0)? ' ' : null) . $sToken;
        }
      }
      else
      {
        // Will the current token start a phrase?
        if (strpos($sQuotes, $sToken[0]) !== false)
        {
          // Will the current token end the phrase?
          if ((strlen($sToken) > 1) && ($sToken[0] === substr($sToken, -1, 1)))
          {
            // The current token begins AND ends the phrase, trim the quotes
            $sPhrase = substr($sToken, 1, -1);
          }
          else
          {
            // Remove the leading quote
            $sPhrase = substr($sToken, 1);
            $cPhraseQuote = $sToken[0];
          }
        }
        else
          $sPhrase = $sToken;
      }
    }

    // If, at this point, we are not within a phrase, the prepared phrase is complete and can be added to the array
    if (($cPhraseQuote === null) && ($sPhrase != null))
    {
      $sPhrase = strtolower($sPhrase);
      $sPhrase = preg_replace('/\s+/', '', $sPhrase);
      if (!in_array($sPhrase, $arTags)) $arTags[] = $sPhrase;
      $sPhrase = null;
    }
  }
  while ($sToken !== false);  // Stop when we receive FALSE from strtok()
  return $arTags;
}

/**
 * Reverses ParseTagString()
 */
function create_tag_str($arTags)
{
  // Prepare each tag to be imploded
  for ($i = 0; $i < sizeof($arTags); $i++)
  {
    // Record findings
    $bContainsWhitespace = false; // Was whitespace found?
    $cRequiredQuote = '"';      // Use double-quote by default
    $cLastChar = null;

    // Search the tag
    for ($j = 0; $j < strlen($arTags[$i]); $j++)
    {
      $c = $arTags[$i][$j];

      // If the current character is a space
      if ($c === ' ')
      {
        $bContainsWhitespace = true;

        // If the previous char was a double quote, we require single quotes round our phrase
        if ($cLastChar === '"')
        {
          $cRequiredQuote = "'";
          break;  // There is no more point in continuing our search, we cant handle double-mixed quotes
        }
      }

      // Record this char as the last char
      $cLastChar = $c;
    }

    // Quote if necessary
    if ($bContainsWhitespace) $arTags[$i] = $cRequiredQuote . $arTags[$i] . $cRequiredQuote;
  }
  return implode(' ', $arTags);
}
?>
