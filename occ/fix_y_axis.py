#!/usr/bin/env python3

#conda install -c dlr-sc pythonocc-core
#conda install -c dlr-sc/label/dev pythonocc-core

import sys
import os

path = sys.argv[1]

from OCC.Core.STEPControl import STEPControl_Reader
from OCC.Core.BRepBndLib import brepbndlib_Add
from OCC.Core.Bnd import Bnd_Box
from OCC.Core.gp import gp_Ax3, gp_Ax2, gp_Ax1, gp_Dir, gp_Pnt
from OCC.Core.TopLoc import TopLoc_Location
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

def main(file_path):
    # Abrir o arquivo STEP
    shape = open_step_file(file_path)

    # Calcular a bounding box
    bbox = calculate_bounding_box(shape)
    dx, dy, dz, x_min, y_min, z_min, x_max, y_max, z_max = get_bounding_box_dimensions(bbox)

    # Verificar se duas dimensões da bounding box são iguais
    dimensions = [(dx, 'X'), (dy, 'Y'), (dz, 'Z')]
    equal_dims = [(d1, d2) for i, (d1, ax1) in enumerate(dimensions) for d2, ax2 in dimensions[i+1:] if math.isclose(d1, d2, rel_tol=1e-6)]

    if equal_dims:
        # Pegue as dimensões iguais e o eixo atual do sistema
        dimension_pair = equal_dims[0]  # Consideramos apenas a primeira combinação
        print(f"Dimensões iguais: {dimension_pair}")

        # Verifique a orientação do eixo Y e sua posição em relação ao centro da face
        center_x = (x_min + x_max) / 2
        center_y = (y_min + y_max) / 2
        center_z = (z_min + z_max) / 2

        # Atualize o sistema de coordenadas
        if dy not in (dimension_pair[0], dimension_pair[1]):
            print("Atualizando o sistema de coordenadas...")
            center_face = gp_Pnt(center_x, center_y, center_z)

            # Novo eixo Y
            normal = gp_Dir(0, 1, 0)  # Y perpendicular e centralizado
            new_axis = gp_Ax3(center_face, normal)
            
            # Transformação
            transformation = gp_Trsf()
            transformation.SetTransformation(new_axis)
            transformed_shape = transform_shape(shape, transformation)
            print("Sistema de coordenadas atualizado.")
            return transformed_shape
        else:
            print("Não foi necessário ajustar o sistema de coordenadas.")
    else:
        print("Nenhuma condição satisfeita para ajuste do sistema de coordenadas.")

if __name__ == "__main__":
    file_path = path #"seu_arquivo.step"  # Insira o caminho do seu arquivo STEP
    main(file_path)
