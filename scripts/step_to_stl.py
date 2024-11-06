from OCC.Core.STEPControl import STEPControl_Reader
from OCC.Core.StlAPI import StlAPI_Writer
from OCC.Core import Tesselator
#from OCC.Visualization import Tesselator, atNormal

input_file  = 'step/1.stp'   # input STEP (AP203/AP214 file)
output_file = './myshape.stl'   # output X3D file

step_reader = STEPControl_Reader()
status = step_reader.ReadFile( input_file )

if status == IFSelect_RetDone:  # check status
    failsonly = False
    step_reader.PrintCheckLoad(failsonly, IFSelect_ItemsByEntity)
    step_reader.PrintCheckTransfer(failsonly, IFSelect_ItemsByEntity)
    aResShape = step_reader.Shape(1)
else:
    print("Error:  can't read file.")
    sys.exit(0)

#Tesselator(aResShape).ExportShapeToX3D( output_file )

step_reader.TransferRoot()
myshape = step_reader.Shape()

print("File readed")

# Export to STL
stl_writer = StlAPI_Writer()
stl_writer.SetASCIIMode(True)
if (stl_writer.Write(myshape, output_file)):
    print("Written")
else:
    print("Failed")
    
