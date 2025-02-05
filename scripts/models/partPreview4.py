import os
import sys
from OCC.Core.STEPControl import STEPControl_Reader
from OCC.Display.SimpleGui import init_display
#from OCC.Core.Quantity import Quantity_NOC_WHITE
from OCC.Core.Quantity import (
    Quantity_Color,
    Quantity_NOC_ALICEBLUE,
    Quantity_NOC_ANTIQUEWHITE,
    Quantity_NOC_WHITE
)

# Função para carregar o arquivo STEP
def load_step_file(step_file_path):
    reader = STEPControl_Reader()
    status = reader.ReadFile(step_file_path)
    if status != 1:  # 1 indica sucesso
        raise ValueError(f"Erro ao carregar o arquivo STEP: {step_file_path}")
    reader.TransferRoots()
    shape = reader.OneShape()
    return shape

# Função para configurar a visualização e capturar a imagem
def generate_isometric_view(step_file, output_image):
    # Inicializar display
    display, start_display, add_menu, add_function_to_menu = init_display()
    view = display.View

    # Configurar fundo branco
    #display.View.SetDefaultBackgroundColor(Quantity_NOC_WHITE)
    display.View.SetBgGradientColors(
        #Quantity_Color(Quantity_NOC_ALICEBLUE),
        #Quantity_Color(Quantity_NOC_ANTIQUEWHITE),
        Quantity_Color(Quantity_NOC_WHITE),
        Quantity_Color(Quantity_NOC_WHITE),
        2,
        True,
    )
    # Carregar a peça STEP
    shape = load_step_file(step_file)
    display.DisplayShape(shape, update=True)

    # Configurar a câmera manualmente para visão isométrica
    view.SetViewOrientationDefault()
    view.SetProj(1, -1, 1)  # Vetor de projeção para isométrico
    view.SetUp(0, 0, 1)     # Definir o eixo Z apontando para cima
    view.FitAll()

    # Atualizar visualização e capturar a imagem
    display.View.Update()
    view.Dump(output_image)
    print(f"Imagem isométrica salva em: {output_image}")

if __name__ == "__main__":
    # Verificar argumentos da linha de comando
    if len(sys.argv) < 2:
        print("Uso: python script.py <arquivo_step>")
        sys.exit(1)

    # Nome do arquivo STEP vindo do argumento da linha de comando
    step_file_path = sys.argv[1]
    output_image_path = os.path.splitext(step_file_path)[0] + "_isometric.png"  # Gerar o nome da imagem

    if not os.path.exists(step_file_path):
        print(f"Arquivo STEP não encontrado: {step_file_path}")
        sys.exit(1)

    try:
        generate_isometric_view(step_file_path, output_image_path)
    except Exception as e:
        print(f"Ocorreu um erro: {e}")
        sys.exit(1)
