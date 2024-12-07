#!/usr/bin/env python3

#conda install -c dlr-sc pythonocc-core
#conda install -c dlr-sc/label/dev pythonocc-core

import sys
import os
from OCC.Core.STEPControl import STEPControl_Reader, STEPControl_Writer, STEPControl_AsIs
from OCC.Core.BRepBndLib import brepbndlib_Add
from OCC.Core.Bnd import Bnd_Box
from OCC.Core.gp import gp_Pnt, gp_Dir, gp_Ax3, gp_Trsf, gp_Vec
from OCC.Core.BRepBuilderAPI import BRepBuilderAPI_Transform
import math

def open_step_file(file_path):
    """Abre um arquivo STEP e retorna o shape"""
    step_reader = STEPControl_Reader()
    status = step_reader.ReadFile(file_path)
    if status != 1:
        raise ValueError("Erro ao abrir o arquivo STEP.")
    step_reader.TransferRoot()
    shape = step_reader.Shape()
    return shape

def save_step_file(shape, file_path):
    """Salva um shape em um arquivo STEP"""
    step_writer = STEPControl_Writer()
    step_writer.Transfer(shape, STEPControl_AsIs)
    status = step_writer.Write(file_path)
    if status != 1:
        raise ValueError(f"Erro ao salvar o arquivo STEP em {file_path}.")
    print(f"Shape salvo com sucesso em {file_path}")

def calculate_bounding_box(shape):
    """Calcula a bounding box de um shape"""
    bbox = Bnd_Box()
    brepbndlib_Add(shape, bbox)
    return bbox

def get_bounding_box_dimensions(bbox):
    """Obtém as dimensões da bounding box"""
    x_min, y_min, z_min, x_max, y_max, z_max = bbox.Get()
    dx = x_max - x_min
    dy = y_max - y_min
    dz = z_max - z_min
    return dx, dy, dz, x_min, y_min, z_min, x_max, y_max, z_max

def rotate_to_align_z(shape, equal_dims):
    """Rotaciona a peça para alinhar o eixo Z global normal ao plano das dimensões iguais"""
    # Centro do plano
    bbox = calculate_bounding_box(shape)
    _, _, _, x_min, y_min, z_min, x_max, y_max, z_max = get_bounding_box_dimensions(bbox)
    center_x = (x_min + x_max) / 2
    center_y = (y_min + y_max) / 2
    center_z = (z_min + z_max) / 2
    center = gp_Pnt(center_x, center_y, center_z)

    # Identificar as dimensões iguais
    dim1, dim2 = equal_dims
    if dim1 == dim2:
        raise ValueError("As dimensões não devem ser idênticas.")

    # Vetores para os eixos principais
    axes = {
        'X': gp_Dir(1, 0, 0),
        'Y': gp_Dir(0, 1, 0),
        'Z': gp_Dir(0, 0, 1)
    }

    # Normal ao plano formado pelas dimensões iguais
    normal = gp_Dir(axes[dim1].XYZ().Crossed(axes[dim2].XYZ()))
    new_axis = gp_Ax3(center, normal)

    # Aplicar transformação
    transformation = gp_Trsf()
    transformation.SetTransformation(new_axis)
    transformer = BRepBuilderAPI_Transform(shape, transformation)
    return transformer.Shape()

def main(file_path):
    # Abrir arquivo STEP
    shape = open_step_file(file_path)

    # Calcular a bounding box
    bbox = calculate_bounding_box(shape)
    dx, dy, dz, x_min, y_min, z_min, x_max, y_max, z_max = get_bounding_box_dimensions(bbox)

    # Verificar dimensões iguais
    dimensions = [('X', dx), ('Y', dy), ('Z', dz)]
    equal_dims = [(a1, a2) for (a1, d1) in dimensions for (a2, d2) in dimensions if a1 != a2 and math.isclose(d1, d2, rel_tol=1e-6)]

    if not equal_dims:
        print("Nenhuma dimensão igual encontrada. Nenhuma transformação será aplicada.")
        return

    # Selecionar a primeira combinação de dimensões iguais
    dim1, dim2 = equal_dims[0]
    print(f"Dimensões iguais identificadas: {dim1} e {dim2}")

    # Rotacionar o shape
    rotated_shape = rotate_to_align_z(shape, (dim1, dim2))

    # Gerar nome do arquivo de saída
    base_name, ext = os.path.splitext(file_path)
    output_file_path = f"{base_name}_fixed{ext}"

    # Salvar shape transformado
    save_step_file(rotated_shape, output_file_path)

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Uso: python script.py <arquivo_step>")
        sys.exit(1)

    input_file_path = sys.argv[1]
    main(input_file_path)
