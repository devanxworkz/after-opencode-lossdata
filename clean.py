with open(r'c:\Users\Umesh\adition-pro\displaydashbord\command\src\Components\Nxdetails\index.js', 'r', encoding='utf-8') as f:
    text = f.read()

# Locate the start of the duplicated block by finding the first instance of 'Map' button ending in mobile view
import re

# Look for the exact line that closes the clean mobile view:
#                           <div className="text-[14px] text-gray-600">—</div>
#                         )}
#                       </div>
#                     </div>
#                   </div>
clean_end = '                          <div className="text-[14px] text-gray-600">—</div>\n                        )}\n                      </div>\n                    </div>\n                  </div>'

if clean_end in text:
    print('Found clean end!')
    start_garbage = text.find(clean_end) + len(clean_end)
    end_garbage = text.find('              );', start_garbage)
    
    if end_garbage != -1:
        print('Found end of garbage!')
        new_text = text[:start_garbage] + '\n' + text[end_garbage:]
        with open(r'c:\Users\Umesh\adition-pro\displaydashbord\command\src\Components\Nxdetails\index.js', 'w', encoding='utf-8') as f:
            f.write(new_text)
        print('Cleaned!')
    else:
        print('Could not find end of garbage')
else:
    print('Could not find clean end. Let\'s try another way.')
    # fallback: search for '                  </div>              );'
    match = re.search(r'(\s+)</div>\s+\);\s+?}\)}\s+</div>', text)
    if match:
        print('Found alternative pattern')
