#!/usr/bin/env python3

#conda install -c dlr-sc pythonocc-core
#conda install -c dlr-sc/label/dev pythonocc-core

import sys
import os

path = sys.argv[1]

from OCC.Core.STEPControl import STEPControl_Reader, STEPControl_Writer, STEPControl_AsIs
from OCC.Core.BRepBndLib import brepbndlib_Add
from OCC.Core.Bnd import Bnd_Box
from OCC.Core.gp import gp_Ax3, gp_Dir, gp_Pnt
from OCC.Core.BRepBuilderAPI import BRepBuilderAPI_Transform
from OCC.Core.gp import gp_Trsf
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

def transform_shape(shape, transformation):
    """Aplica uma transformação a um shape"""
    transformer = BRepBuilderAPI_Transform(shape, transformation)
    transformed_shape = transformer.Shape()
    return transformed_shape

def main(input_file_path, output_file_path):
    # Abrir o arquivo STEP
    shape = open_step_file(input_file_path)

    # Calcular a bounding box
    bbox = calculate_bounding_box(shape)
    dx, dy, dz, x_min, y_min, z_min, x_max, y_max, z_max = get_bounding_box_dimensions(bbox)

    # Verificar se duas dimensões da bounding box são iguais
    dimensions = [(dx, 'X'), (dy, 'Y'), (dz, 'Z')]
    equal_dims = [(d1, d2) for i, (d1, ax1) in enumerate(dimensions) for d2, ax2 in dimensions[i+1:] if math.isclose(d1, d2, rel_tol=1e-6)]

    transformed_shape = shape  # Shape original por padrão
    if equal_dims:
        # Pegue as dimensões iguais
        dimension_pair = equal_dims[0]  # Consideramos apenas a primeira combinação
        print(f"Dimensões iguais: {dimension_pair}")

        # Verifique a orientação do eixo Z e sua posição em relação ao centro da face
        center_x = (x_min + x_max) / 2
        center_y = (y_min + y_max) / 2
        center_z = (z_min + z_max) / 2

        # Atualize o sistema de coordenadas
        if dz not in (dimension_pair[0], dimension_pair[1]):
            print("Atualizando o sistema de coordenadas...")
            center_face = gp_Pnt(center_x, center_y, center_z)

            # Novo eixo Z
            normal = gp_Dir(0, 0, 1)  # Z perpendicular e centralizado
            new_axis = gp_Ax3(center_face, normal)
            
            # Transformação
            transformation = gp_Trsf()
            transformation.SetTransformation(new_axis)
            transformed_shape = transform_shape(shape, transformation)
            print("Sistema de coordenadas atualizado.")
        else:
            print("Não foi necessário ajustar o sistema de coordenadas.")
    else:
        print("Nenhuma condição satisfeita para ajuste do sistema de coordenadas.")

    # Salvar o shape transformado em um novo arquivo STEP
    save_step_file(transformed_shape, output_file_path)

if __name__ == "__main__":
    input_file_path = path #"seu_arquivo.step"  # Insira o caminho do arquivo STEP de entrada
    output_file_path = "saida_transformada.step"  # Insira o caminho para o arquivo STEP de saída
    main(input_file_path, output_file_path)
