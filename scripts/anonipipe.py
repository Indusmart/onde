import fitz  # PyMuPDF
import sys
import re

def extract_words_from_pdf(doc):
    """Extrai todas as palavras de um PDF."""
    words = []
    
    for page_num in range(len(doc)):
        page = doc.load_page(page_num)
        text = page.get_text("words")  # Extrai palavras com coordenadas
        for word in text:
            words.append({
                "text": word[4],  # Texto da palavra
                "bbox": (word[0], word[1], word[2], word[3]),  # Bounding box (coordenadas)
                "page": page_num  # Número da página
            })
    
    return words

def is_alphabetic(word):
    """Verifica se uma palavra contém apenas caracteres alfabéticos."""
    return bool(re.match(r'^[a-zA-ZÀ-ÿ]+$', word))

def remove_alphabetic_words_from_pdf(doc):
    """Remove apenas palavras alfabéticas de um PDF."""
    # Extrai todas as palavras do PDF
    words = extract_words_from_pdf(doc)
    
    # Filtra apenas palavras alfabéticas
    alphabetic_words = [word_info for word_info in words if is_alphabetic(word_info["text"])]
    
    # Remove cada palavra alfabética
    for word_info in alphabetic_words:
        page_num = word_info["page"]
        bbox = word_info["bbox"]
        
        page = doc.load_page(page_num)
        page.add_redact_annot(bbox)  # Cria uma anotação de redação na área da palavra
        page.apply_redactions()      # Aplica a redação (remove o conteúdo)
    
    return doc

if __name__ == "__main__":
    print("passei")
    # Lê o PDF da entrada padrão (stdin)
    input_pdf_data = sys.stdin.buffer.read()
    
    # Abre o PDF a partir dos dados lidos
    doc = fitz.open("pdf", input_pdf_data)
    
    # Remove palavras alfabéticas
    modified_doc = remove_alphabetic_words_from_pdf(doc)
    
    # Salva o PDF modificado na saída padrão (stdout)
    sys.stdout.buffer.write(modified_doc.write())
