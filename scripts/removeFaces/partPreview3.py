import os
import sys
from OCC.Core.STEPControl import STEPControl_Reader
from OCC.Display.SimpleGui import init_display
from OCC.Core.V3d import V3d_TypeOfOrientation
from OCC.Core.AIS import AIS_Shape
from OCC.Core.TopoDS import TopoDS_Shape
from OCC.Core.Prs3d import Prs3d_Drawer
from OCC.Core.Graphic3d import Graphic3d_TypeOfShadingModel
from OCC.Core.Quantity import Quantity_Color
from OCC.Display.OCCViewer import Viewer3d
from PIL import Image

# Função para carregar o arquivo STEP
def load_step_file(step_file_path):
    reader = STEPControl_Reader()
    status = reader.ReadFile(step_file_path)
    if status != 1:  # 1 indica sucesso
        raise ValueError(f"Erro ao carregar o arquivo STEP: {step_file_path}")
    reader.TransferRoots()
    shape = reader.OneShape()
    return shape

# Função principal para renderizar a imagem com fundo transparente
def render_with_transparent_background(step_file_path, output_image_path):
    # Inicializar o visualizador
    viewer = Viewer3d()
    viewer.Create()
    view = viewer.View

    # Configurar a cor de fundo como preto (RGB) para posterior processamento
    background_color = Quantity_Color(0.0, 0.0, 0.0, 0)  # Preto
    view.SetBackgroundColor(background_color)

    # Carregar o arquivo STEP
    shape = load_step_file(step_file_path)
    ais_shape = AIS_Shape(shape)

    # Configurar a cena
    ais_context = viewer.Context
    ais_context.Display(ais_shape, True)
    view.SetProj(V3d_TypeOfOrientation.V3d_XposYnegZpos)  # Vista isométrica
    view.FitAll()

    # Renderizar para arquivo temporário
    temp_image_path = os.path.splitext(output_image_path)[0] + "_temp.png"
    if not view.Dump(temp_image_path):
        raise RuntimeError("Erro ao salvar a imagem com fundo preto")

    # Processar o fundo para transparência
    with Image.open(temp_image_path) as img:
        img = img.convert("RGBA")
        datas = img.getdata()
        new_data = []
        for item in datas:
            # Substituir pixels pretos por transparência
            if item[:3] == (0, 0, 0):
                new_data.append((0, 0, 0, 0))  # Transparente
            else:
                new_data.append(item)
        img.putdata(new_data)
        img.save(output_image_path)

    # Remover o arquivo temporário
    os.remove(temp_image_path)
    print(f"Imagem salva com fundo transparente em: {output_image_path}")

# Execução principal
if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Uso: python script.py <arquivo_step>")
        sys.exit(1)

    step_file = sys.argv[1]
    output_image = os.path.splitext(step_file)[0] + "_transparent.png"

    try:
        render_with_transparent_background(step_file, output_image)
    except Exception as e:
        print(f"Erro: {e}")
