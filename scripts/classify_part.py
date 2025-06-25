import sys
import numpy as np
from OCC.Core.STEPControl import STEPControl_Reader
from OCC.Core.TopExp import TopExp_Explorer
from OCC.Core.TopAbs import TopAbs_FACE
from OCC.Core.BRep import BRep_Tool
from OCC.Core.BRepGProp import brepgprop_SurfaceProperties #BRepGProp_SurfaceProperties
from OCC.Core.GProp import GProp_GProps
from OCC.Core.GeomLProp import GeomLProp_SurfaceTool
from OCC.Core.BRepAdaptor import BRepAdaptor_Surface
from OCC.Core.GeomAbs import GeomAbs_Cylinder, GeomAbs_Plane

def load_step_file(file_path):
    """ Carrega o arquivo STEP """
    step_reader = STEPControl_Reader()
    status = step_reader.ReadFile(file_path)

    if status != 1:
        raise ValueError("Erro ao abrir o arquivo STEP.")

    step_reader.TransferRoot()
    shape = step_reader.Shape()

    return shape

def analyze_geometry(shape):
    """ Analisa a geometria da peça e classifica como torno ou fresa """
    explorer = TopExp_Explorer(shape, TopAbs_FACE)

    cylinder_count = 0
    plane_count = 0

    while explorer.More():
        face = explorer.Current()
        surf = BRepAdaptor_Surface(face)
        surf_type = surf.GetType()

        if surf_type == GeomAbs_Cylinder:
            cylinder_count += 1
        elif surf_type == GeomAbs_Plane:
            plane_count += 1

        explorer.Next()

    return cylinder_count, plane_count

def classify_part(file_path):
    """ Classifica a peça com base nas características geométricas """
    shape = load_step_file(file_path)
    cylinder_count, plane_count = analyze_geometry(shape)

    if cylinder_count > plane_count:
        return "TORNO"
    elif plane_count > cylinder_count:
        return "FRESA"
    else:
        return "MISTO (Torno + Fresa)"

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Uso: python classify_part.py arquivo.step")
        sys.exit(1)

    file_path = sys.argv[1]
    classification = classify_part(file_path)
    print(f"Classificação da peça: {classification}")
# Como Usar
# Salve o código acima como classify_part.py
# Execute o script passando um arquivo STEP como argumento:
# bash
# Copiar
# Editar
# python classify_part.py minha_peca.step
# O código retornará:
# "TORNO" se a peça tiver mais superfícies cilíndricas.
# "FRESA" se a peça tiver mais superfícies planas.
# "MISTO (Torno + Fresa)" se a peça contiver ambas de forma equilibrada.
# Explicação Técnica
# OCC (OpenCascade) e pythonOCC são utilizados para processar arquivos STEP.
# BRepAdaptor_Surface identifica se as faces são cilíndricas (torno) ou planas (fresa).
# O algoritmo conta quantas faces cilíndricas e planas a peça possui e classifica com base nessa proporção.
# Possíveis Melhorias
# Analisar diâmetro e altura dos cilindros para identificar eixos ou flanges.
# Detectar padrões como furos passantes ou roscados.
# Criar um dashboard visual para exibir a análise.
